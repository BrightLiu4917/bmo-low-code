<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\Resident;

use BrightLiu\LowCode\Enums\Model\Resident\DataSourceEnum;
use BrightLiu\LowCode\Services\DtCapybaraServiceRcApiService;
use BrightLiu\LowCode\Support\CrowdConnection;
use BrightLiu\LowCode\Traits\Context\WithContext;
use Carbon\Carbon;
use Gupo\BetterLaravel\Service\BaseService;
use Gupo\DBQuery\DBQuery;
use Illuminate\Support\Facades\Cache;

/**
 * 居民指标的 CDR 检验历史
 */
class ResidentMetricCdrService extends BaseService
{
    use WithContext;

    /**
     * 拉取单个指标的 CDR 检验历史，失败时返回空数组
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRows(
        string $empi,
        string $metricId,
        string|Carbon|null $minDate = null,
        string|Carbon|null $maxDate = null,
    ): array {
        if ($metricId === '') {
            return [];
        }

        $grouped = $this->getRowsByMetric($empi, [$metricId], $minDate, $maxDate);

        return $grouped[$metricId] ?? [];
    }

    /**
     * CDR 检验历史条数，失败时返回 0
     */
    public function getCount(
        string $empi,
        string $metricId,
        string|Carbon|null $minDate = null,
        string|Carbon|null $maxDate = null,
    ): int {
        return count($this->getRows($empi, $metricId, $minDate, $maxDate));
    }

    /**
     * 按指标拉取 CDR 检验历史。映射或单个指标失败时跳过，不影响其余指标
     *
     * @param string[] $metricIds
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function getRowsByMetric(
        string $empi,
        array $metricIds,
        string|Carbon|null $minDate = null,
        string|Carbon|null $maxDate = null,
    ): array {
        if (!$this->enabled() || $empi === '' || $metricIds === []) {
            return [];
        }

        $orgCode = $this->getOrgCode();
        if ($orgCode === '') {
            return [];
        }

        $baselineDbConfig = config('low-code.bmo-baseline.database.default');
        if (empty($baselineDbConfig)) {
            return [];
        }

        try {
            $mappings = DBQuery::connection($baselineDbConfig)
                ->getConnection()
                ->table('patient_field_cdr_mapping')
                ->where('org_code', $orgCode)
                ->whereIn('field_key', $metricIds)
                ->where('status', 1)
                ->where('is_deleted', 0)
                ->get(['field_key', 'cdr_field_key']);
        } catch (\Throwable $e) {
            logs()->error('查询居民指标 CDR 字段映射失败', [
                'empi' => $empi,
                'org_code' => $orgCode,
                'metric_ids' => $metricIds,
                'error_msg' => $e->getMessage(),
            ]);

            return [];
        }

        $cdrKeysByField = [];
        foreach ($mappings as $mapping) {
            $mapping = (array) $mapping;
            $fieldKey = (string) ($mapping['field_key'] ?? '');
            $cdrFieldKey = (string) ($mapping['cdr_field_key'] ?? '');
            if ($fieldKey === '' || $cdrFieldKey === '') {
                continue;
            }
            $cdrKeysByField[$fieldKey][$cdrFieldKey] = $cdrFieldKey;
        }

        if ($cdrKeysByField === []) {
            return [];
        }

        $cardNo = $this->resolveIdCardNo($empi);
        if ($cardNo === null) {
            return [];
        }

        $startTime = null;
        $endTime = null;
        if (!empty($minDate) && !empty($maxDate)) {
            $startTime = $this->formatQueryDate($minDate);
            $endTime = $this->formatQueryDate($maxDate);
            if ($startTime === null || $endTime === null) {
                return [];
            }
        }

        $rowsByField = [];
        foreach ($cdrKeysByField as $fieldKey => $cdrFieldKeys) {
            foreach ($cdrFieldKeys as $cdrFieldKey) {
                try {
                    $params = [
                        'test_itm_cd' => $cdrFieldKey,
                        'id_crd_no' => $cardNo,
                        'org_code' => $orgCode,
                    ];
                    if ($startTime !== null) {
                        $params['start_time'] = $startTime;
                    }
                    if ($endTime !== null) {
                        $params['end_time'] = $endTime;
                    }

                    $list = DtCapybaraServiceRcApiService::make()->getPatientTestResultHistory($params);
                    foreach ($list as $item) {
                        if (!is_array($item)) {
                            $item = (array) $item;
                        }
                        $rowsByField[$fieldKey][] = [
                            'id' => 0,
                            'col_value' => (string) ($item['test_rslt_vlu'] ?? ''),
                            'fill_date' => (string) ($item['test_dt'] ?? ''),
                            'data_source' => DataSourceEnum::CDR_TEST,
                            'bsns_no' => '',
                        ];
                    }
                } catch (\Throwable $e) {
                    logs()->error('获取居民指标 CDR 检验历史失败', [
                        'empi' => $empi,
                        'org_code' => $orgCode,
                        'metric_id' => $fieldKey,
                        'cdr_field_key' => $cdrFieldKey,
                        'error_msg' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $rowsByField;
    }

    /**
     * 合并原指标与 CDR 检验历史，并按测量时间排序。无 CDR 时原样返回
     *
     * @param array<int, mixed> $originRows
     * @param array<int, array<string, mixed>> $cdrRows
     *
     * @return array<int, mixed>
     */
    public function merge(array $originRows, array $cdrRows, string $sort = 'asc'): array
    {
        if ($cdrRows === []) {
            return $originRows;
        }

        try {
            $rows = [];
            $seq = 0;
            foreach ($originRows as $row) {
                $item = (array) $row;
                $item['_seq'] = $seq++;
                $rows[] = $item;
            }
            foreach ($cdrRows as $row) {
                $row['_seq'] = $seq++;
                $rows[] = $row;
            }

            $desc = $sort === 'desc';
            usort($rows, function (array $left, array $right) use ($desc): int {
                $cmp = strcmp((string) ($left['fill_date'] ?? ''), (string) ($right['fill_date'] ?? ''));
                if (0 === $cmp) {
                    return ($left['_seq'] ?? 0) <=> ($right['_seq'] ?? 0);
                }

                return $desc ? -$cmp : $cmp;
            });

            foreach ($rows as &$row) {
                unset($row['_seq']);
            }
            unset($row);

            return $rows;
        } catch (\Throwable $e) {
            logs()->error('合并居民指标 CDR 检验历史失败', [
                'error_msg' => $e->getMessage(),
            ]);

            return $originRows;
        }
    }

    /**
     * CDR 时间更晚时覆盖最新值，否则返回原值
     *
     * @param array{value: mixed, latest_at: mixed, data_source: mixed, latest_id: mixed} $current
     * @param array<int, array<string, mixed>> $cdrRows
     *
     * @return array{value: mixed, latest_at: mixed, data_source: mixed, latest_id: mixed}
     */
    public function applyLatest(array $current, array $cdrRows): array
    {
        $latestCdr = $this->pickLatest($cdrRows);
        $cdrTime = (string) ($latestCdr['fill_date'] ?? '');
        $latestAt = $current['latest_at'] ?? null;
        if (
            $latestCdr === null
            || $cdrTime === ''
            || ($latestAt !== null && $latestAt !== '' && strcmp($cdrTime, (string) $latestAt) <= 0)
        ) {
            return $current;
        }

        return [
            'value' => $latestCdr['col_value'],
            'latest_at' => $cdrTime,
            'data_source' => DataSourceEnum::CDR_TEST,
            'latest_id' => null,
        ];
    }

    /**
     * 取 CDR 记录中测量时间最晚的一条
     *
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<string, mixed>|null
     */
    public function pickLatest(array $rows): ?array
    {
        $latest = null;
        foreach ($rows as $row) {
            $time = (string) ($row['fill_date'] ?? '');
            if ($time === '') {
                continue;
            }
            if ($latest === null || strcmp($time, (string) ($latest['fill_date'] ?? '')) > 0) {
                $latest = $row;
            }
        }

        return $latest;
    }

    /**
     * 是否从资源中心 CDR 补充检验历史
     */
    public function enabled(): bool
    {
        return (bool) config('low-code.resident-metric.cdr-source-enabled', false);
    }

    /**
     * 按居民主索引解析身份证号，沿用上游查询的缓存
     */
    private function resolveIdCardNo(string $empi): ?string
    {
        if ($empi === '' || empty($psnTable = config('low-code.bmo-baseline.database.crowd-psn-wdth-table'))) {
            return null;
        }

        try {
            $cardNo = Cache::remember(
                'resident:' . md5('resolve_card_no:' . $psnTable . $empi),
                60 * 30,
                fn () => CrowdConnection::table($psnTable)->where('empi', $empi)->value('id_crd_no')
            );
        } catch (\Throwable $e) {
            logs()->error('获取居民身份证号失败', [
                'empi' => $empi,
                'error_msg' => $e->getMessage(),
            ]);

            return null;
        }

        if (empty($cardNo)) {
            return null;
        }

        return (string) $cardNo;
    }

    /**
     * 资源中心时间参数，只传日期
     */
    private function formatQueryDate(string|Carbon|null $time): ?string
    {
        if ($time instanceof Carbon) {
            return $time->format('Y-m-d');
        }

        if (!is_string($time) || $time === '') {
            return null;
        }

        return Carbon::make($time)?->format('Y-m-d');
    }
}
