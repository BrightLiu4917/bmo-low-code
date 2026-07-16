<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\Resident;

use App\Support\Tools\BetterArr;
use BrightLiu\LowCode\Models\Resident\ResidentMonitorMetric;
use BrightLiu\LowCode\Services\BmpCheetahMedicalCrowdkitApiService;
use BrightLiu\LowCode\Services\BmpCheetahMedicalPlatformApiService;
use BrightLiu\LowCode\Support\CrowdConnection;
use BrightLiu\LowCode\Tools\Clock;
use BrightLiu\LowCode\Traits\Context\WithContext;
use Carbon\Carbon;
use Gupo\BetterLaravel\Service\BaseService;
use Gupo\DBQuery\DBQuery;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 居民监测指标相关
 */
class ResidentMetricService extends BaseService
{
    use WithContext;

    /**
     * 根据档案趋势默认配置（仅指标 ID）构建监测指标列表
     */
    public function buildMonitorListFromArchiveTrendConfig(array $archiveTrendConfig): Collection
    {
        $metricIds = $this->resolveArchiveTrendMetricIds($archiveTrendConfig);
        if (empty($metricIds)) {
            return collect();
        }

        $metricOptional = $this->getMetricOptionalMap();

        return collect($metricIds)->map(function (string $metricId) use ($metricOptional) {
            $optional = $metricOptional[$metricId] ?? [];
            $metricTitle = (string) ($optional['field_name'] ?? '');

            if ('' === $metricTitle) {
                return null;
            }

            $item = new ResidentMonitorMetric([
                'metric_id' => $metricId,
                'metric_title' => $metricTitle,
            ]);
            $item->offsetSet('group_name', (string) ($optional['col_group_name'] ?? ''));

            return $item;
        })->filter()->values();
    }

    /**
     * 为监测指标列表追加分组名称
     */
    public function enrichMonitorListWithGroupName(Collection $data): void
    {
        $metricOptional = $this->getMetricOptionalMap();
        if (empty($metricOptional)) {
            return;
        }

        $data->each(function ($item) use ($metricOptional) {
            $item->offsetSet('group_name', $metricOptional[$item->metric_id]['col_group_name'] ?? '');
        });
    }

    /**
     * 解析档案趋势配置中的指标 ID 列表
     */
    public function resolveArchiveTrendMetricIds(array $archiveTrendConfig): array
    {
        if (isset($archiveTrendConfig['metric_ids']) && is_array($archiveTrendConfig['metric_ids'])) {
            return $this->normalizeMetricIds($archiveTrendConfig['metric_ids']);
        }

        return $this->normalizeMetricIds($archiveTrendConfig);
    }

    /**
     * @return array<string, array>
     */
    private function getMetricOptionalMap(): array
    {
        return array_column(
            rescue(fn () => BmpCheetahMedicalCrowdkitApiService::make()->getMetricOptional(), []),
            null,
            'field'
        );
    }

    /**
     * @param array<int|string, mixed> $items
     *
     * @return array<int, string>
     */
    private function normalizeMetricIds(array $items): array
    {
        $ids = [];
        foreach ($items as $item) {
            if (is_string($item) && '' !== $item) {
                $ids[] = $item;

                continue;
            }
            if (!is_array($item)) {
                continue;
            }
            $id = $item['metric_id'] ?? $item['field'] ?? $item['indicator_id'] ?? null;
            if (is_string($id) && '' !== $id) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * 保存监测指标
     *
     * @param string $empi 居民主索引
     * @param array $metricIds 指标ID
     */
    public function saveMonitor(string $empi, array $metricIds = []): bool
    {
        $optionalMetrics = BmpCheetahMedicalCrowdkitApiService::make()->getMetricOptional();
        $optionalMetricMap = array_column($optionalMetrics, 'field_name', 'field');

        // TODO: 写法待完善
        DB::transaction(function () use ($empi, $metricIds, $optionalMetricMap) {
            ResidentMonitorMetric::query()
                ->where('disease_code', $this->getDiseaseCode())
                ->where('scene_code', $this->getSceneCode())
                ->where('resident_empi', $empi)
                ->delete();

            if (!empty($metricIds)) {
                ResidentMonitorMetric::query()->insert(
                    array_values(array_filter(
                        array_map(
                            fn ($metricId) => [
                                'disease_code' => $this->getDiseaseCode(),
                                'scene_code' => $this->getSceneCode(),
                                'resident_empi' => $empi,
                                'metric_title' => $optionalMetricMap[$metricId] ?? null,
                                'metric_id' => $metricId,
                                'created_at' => Clock::now(),
                            ],
                            $metricIds
                        ),
                        fn ($item) => !empty($item['metric_title'])
                    ))
                );
            }
        });

        return true;
    }

    /**
     * 获取 监测指标趋势
     */
    public function getMonitorTrendItems(
        string $empi,
        string $metricId,
        string|Carbon|null $minDate = null,
        string|Carbon|null $maxDate = null,
        int $limit = 0
    ): array {
        $metricConfig = null;
        if (config('low-code.resident-archive.metric-from-upstream-enabled', false)) {
            // 获取上游指标
            $personalArchiveConfig = BmpCheetahMedicalCrowdkitApiService::make()->getPersonalArchiveConfig();
            $personalArchiveFields = array_column($personalArchiveConfig['data'] ?? [], null, 'src_col_name');

            $metricConfig = $personalArchiveFields[$metricId] ?? null;
        }

        return match (true) {
            // 存在指标配置时，为上游指标
            !is_null($metricConfig) => $this->getMonitorTrendItemsByUpstream(
                $empi, $metricId, $minDate, $maxDate, $limit, $metricConfig
            ),
            default => $this->getMonitorTrendItemsByBusiness(
                $empi, $metricId, $minDate, $maxDate, $limit
            ),
        };
    }

    /**
     * 获取 监测指标趋势数量统计
     */
    public function getMonitorTrendCount(
        string $empi,
        string $metricId,
        string|Carbon|null $minDate = null,
        string|Carbon|null $maxDate = null
    ): int {
        $metricConfig = null;
        if (config('low-code.resident-archive.metric-from-upstream-enabled', false)) {
            // 获取上游指标
            $personalArchiveConfig = BmpCheetahMedicalCrowdkitApiService::make()->getPersonalArchiveConfig();
            $personalArchiveFields = array_column($personalArchiveConfig['data'] ?? [], null, 'src_col_name');

            $metricConfig = $personalArchiveFields[$metricId] ?? null;
        }

        return match (true) {
            // 存在指标配置时，为上游指标
            !is_null($metricConfig) => $this->getMonitorTrendCountByUpstream(
                $empi, $metricId, $minDate, $maxDate, $metricConfig
            ),
            default => $this->getMonitorTrendCountByBusiness(
                $empi, $metricId, $minDate, $maxDate
            ),
        };
    }

    /**
     * 获取来自业务的监测指标趋势数量统计
     */
    public function getMonitorTrendCountByBusiness(
        string $empi,
        string $metricId,
        string|Carbon|null $minDate = null,
        string|Carbon|null $maxDate = null
    ): int {
        $connection = null;

        // 优先使用内置连接
        if (!empty($baselineDbConfig = config('low-code.bmo-baseline.database.default'))) {
            $connection = DBQuery::connection($baselineDbConfig)->getConnection()->table('personal_archive');
        } else {
            $connection = CrowdConnection::table('personal_archive');
        }

        return (int) $connection
            ->where('col_name', $metricId)
            ->where('empi', $empi)
            ->when(!empty($minDate) && !empty($maxDate), fn ($query) => $query
                ->whereBetween('fill_date', [Carbon::make($minDate)->startOfDay(), Carbon::make($maxDate)->endOfDay()])
            )
            ->count();
    }

    /**
     * 获取来自上游的监测指标趋势数量统计
     */
    public function getMonitorTrendCountByUpstream(
        string $empi,
        string $metricId,
        string|Carbon|null $minDate,
        string|Carbon|null $maxDate,
        array $metricConfig
    ): int {
        // 根据empi获取身份证号
        $cardNo = null;
        if (!empty($psnTable = config('low-code.bmo-baseline.database.crowd-psn-wdth-table'))) {
            $cardNo = Cache::remember(
                'resident:' . md5('resolve_card_no:' . $psnTable . $empi),
                60 * 30,
                fn () => CrowdConnection::table($psnTable)->where('empi', $empi)->value('id_crd_no')
            );
        }

        if (empty($cardNo)) {
            return 0;
        }

        // 业务时间字段(用于排序及区间查询)
        $businessDateField = $metricConfig['time_col'] ?? 'upd_tm';

        // 目标字段
        $columnName = $metricConfig['tgt_col_name'];

        $query = CrowdConnection::table($metricConfig['tbl_name'])
            ->where('id_crd_no', $cardNo);

        if (0 != $metricConfig['is_vertical']) {
            $query->where('item_name', $columnName);
        }

        return (int) $query
            ->when(!empty($minDate) && !empty($maxDate), fn ($query) => $query
                ->whereBetween($businessDateField, [Carbon::make($minDate)->startOfDay(), Carbon::make($maxDate)->endOfDay()])
            )
            ->count();
    }

    /**
     * 获取来自业务的监测指标趋势
     */
    public function getMonitorTrendItemsByBusiness(
        string $empi,
        string $metricId,
        string|Carbon|null $minDate = null,
        string|Carbon|null $maxDate = null,
        int $limit = 0
    ): array {
        $connection = null;

        // 优先使用内置连接
        if (!empty($baselineDbConfig = config('low-code.bmo-baseline.database.default'))) {
            $connection = DBQuery::connection($baselineDbConfig)->getConnection()->table('personal_archive');
        } else {
            $connection = CrowdConnection::table('personal_archive');
        }

        return $connection
            ->where('col_name', $metricId)
            ->where('empi', $empi)
            ->whereBetweenDate('fill_date', $minDate, $maxDate, forceFullDay: true)
            ->when($limit > 0, fn ($query) => $query->limit($limit))
            ->get(['col_value', 'fill_date', 'data_source'])
            ->sortBy('fill_date')
            ->toArray();
    }

    /**
     * 获取来自上游的监测指标趋势
     */
    public function getMonitorTrendItemsByUpstream(
        string $empi,
        string $metricId,
        string|Carbon|null $minDate,
        string|Carbon|null $maxDate,
        int $limit,
        array $metricConfig
    ): array {
        // 根据empi获取身份证号
        $cardNo = null;
        if (!empty($psnTable = config('low-code.bmo-baseline.database.crowd-psn-wdth-table'))) {
            $cardNo = Cache::remember(
                'resident:' . md5('resolve_card_no:' . $psnTable . $empi),
                60 * 30,
                fn () => CrowdConnection::table($psnTable)->where('empi', $empi)->value('id_crd_no')
            );
        }

        if (empty($cardNo)) {
            return [];
        }

        // 业务时间字段(用于排序及区间查询)
        $businessDateField = $metricConfig['time_col'] ?? 'upd_tm';

        // 目标字段
        $columnName = $metricConfig['tgt_col_name'];

        if (0 == $metricConfig['is_vertical']) {
            return CrowdConnection::table($metricConfig['tbl_name'])
                ->where('id_crd_no', $cardNo)
                ->when(!empty($minDate) && !empty($maxDate), fn ($query) => $query
                    ->whereBetween($businessDateField, [Carbon::make($minDate)->startOfDay(), Carbon::make($maxDate)->endOfDay()])
                )
                ->when($limit > 0, fn ($query) => $query->limit($limit))
                ->get(["{$businessDateField} as fill_date", "{$columnName} as col_value"])
                ->sortBy($businessDateField)
                ->toArray();
        }

        return CrowdConnection::table($metricConfig['tbl_name'])
            ->where('id_crd_no', $cardNo)
            ->where('item_name', $columnName)
            ->when(!empty($minDate) && !empty($maxDate), fn ($query) => $query
                ->whereBetween($businessDateField, [Carbon::make($minDate)->startOfDay(), Carbon::make($maxDate)->endOfDay()])
            )
            ->when($limit > 0, fn ($query) => $query->limit($limit))
            ->get(["{$businessDateField} as fill_date", 'item_value as col_value'])
            ->sortBy($businessDateField)
            ->toArray();
    }

    /**
     * 获取患者人口学信息（性别、出生日期）
     *
     * @return array{gender: int|null, bth_dt: string|null}|null
     */
    public function resolvePatientDemographic(string $empi): ?array
    {
        $psnTable = config('low-code.bmo-baseline.database.crowd-psn-wdth-table');
        if (empty($psnTable)) {
            return null;
        }

        return Cache::remember(
            'resident:' . md5('demographic:' . $psnTable . $empi),
            60 * 30,
            fn () => rescue(
                function () use ($psnTable, $empi) {
                    $row = CrowdConnection::table($psnTable)
                        ->where('empi', $empi)
                        ->first(['gdr_cd', 'bth_dt']);

                    if (empty($row)) {
                        return null;
                    }

                    $row = (array) $row;

                    // gdr_cd 性别代码映射为 gender: 1=男 2=女, 其他视为未知
                    $gdrCd = (string) ($row['gdr_cd'] ?? '');
                    $row['gender'] = match ($gdrCd) {
                        '1' => 1,
                        '2' => 2,
                        default => null,
                    };

                    return $row;
                },
                null
            )
        );
    }

    /**
     * 获取预警规则（带缓存）
     *
     * @return array<int, array>
     */
    public function getVitalsWarningRules(array|string $metricId): array
    {
        $metricIds = Arr::wrap($metricId);

        $cacheEnabled = config('low-code.resident-metric.warning-rule-cache.enabled', false);
        $cacheTtl = (int) config('low-code.resident-metric.warning-rule-cache.ttl', 1800);

        if ($cacheEnabled) {
            $cacheKey = 'resident:metric_warning_rules:' . md5(join(',', $metricIds));

            return Cache::remember($cacheKey, $cacheTtl, function () use ($metricIds) {
                return BmpCheetahMedicalPlatformApiService::make()->getVitalsWarningRules($metricIds);
            });
        }

        return BmpCheetahMedicalPlatformApiService::make()->getVitalsWarningRules($metricIds);
    }

    /**
     * 匹配预警规则
     *
     * @param float $value 监测值
     * @param array $rules 规则列表
     * @param string $dataDate 数据日期
     * @param string|null $birthDate 患者出生日期
     * @param int|null $gender 患者性别 1=男 2=女
     *
     * @return array{tag: string, remark: string, risk_level_color: string, dispose_advice: string, direction: int}|null
     */
    public function matchWarning(?float $value, array $rules, string $dataDate, ?string $birthDate, ?int $gender = null): ?array
    {
        if (empty($rules) || empty($dataDate) || empty($gender)) {
            return null;
        }

        // 推算当前数据点的患者年龄
        $age = null;
        if (!empty($birthDate)) {
            try {
                $age = Carbon::make($birthDate)->diffInYears(Carbon::make($dataDate));
            } catch (\Throwable) {
                $age = null;
            }
        }

        $matched = null;
        $matchedRange = PHP_FLOAT_MAX;

        foreach ($rules as $rule) {
            // 性别过滤：rule.sex=0 表示不限，否则需匹配患者性别
            if (null !== $gender) {
                $ruleSex = (int) ($rule['sex'] ?? 0);
                if (0 !== $ruleSex && $ruleSex !== $gender) {
                    continue;
                }
            }

            // 年龄过滤（仅在能推算年龄时）
            if (null !== $age) {
                $minAge = (int) ($rule['min_age'] ?? -1);
                $maxAge = (int) ($rule['max_age'] ?? 999999);
                if (-1 !== $minAge && $age < $minAge) {
                    continue;
                }
                if (999999 !== $maxAge && $age > $maxAge) {
                    continue;
                }
            }

            // 值范围匹配
            $minVal = (float) ($rule['min_val'] ?? -1);
            $maxVal = (float) ($rule['max_val'] ?? 999999);

            if ($value >= $minVal && $value <= $maxVal) {
                $range = $maxVal - $minVal;
                if ($range < $matchedRange) {
                    $matchedRange = $range;
                    $matched = [
                        'tag' => (string) ($rule['tag'] ?? ''),
                        'remark' => (string) ($rule['remark'] ?? ''),
                        'risk_level_color' => (string) ($rule['risk_level_color'] ?? ''),
                        'dispose_advice' => (string) ($rule['dispose_advice'] ?? ''),
                        'direction' => (int) ($rule['direction'] ?? 0),
                    ];
                }
            }
        }

        return $matched;
    }

    /**
     * 匹配某字段的预警规则
     *
     * @param string $field 字段名
     * @param float $value 监测值
     * @param array $rules 规则列表
     * @param string $dataDate 数据日期
     * @param string|null $birthDate 患者出生日期
     * @param int|null $gender 患者性别 1=男 2=女
     *
     * @return array{tag: string, remark: string, risk_level_color: string, dispose_advice: string, direction: int}|null
     */
    public function matchFieldWarning(string $field, ?float $value, array $rules, string $dataDate, ?string $birthDate, ?int $gender = null): ?array
    {
        $rules = array_values(
            array_filter($rules, fn ($rule) => ($rule['field_code'] ?? '') === $field)
        );

        return $this->matchWarning($value, $rules, $dataDate, $birthDate, $gender);
    }

    /**
     * 为监测趋势数据批量附加预警信息
     *
     * 内部完成：获取患者人口学信息 → 获取预警规则 → 逐条匹配预警
     *
     * @param array<int, array> $items 监测数据
     * @param string $empi 居民主索引
     * @param string $metricId 指标ID
     *
     * @return array<int, array>
     */
    public function attachWarningToItems(array $items, string $empi, string $metricId): array
    {
        $items = BetterArr::toArray($items);

        // 获取患者人口学信息
        $demographic = $this->resolvePatientDemographic($empi);
        $birthDate = $demographic['bth_dt'] ?? null;
        $gender = $demographic['gender'] ?? null;

        // 获取预警规则
        $rules = $this->getVitalsWarningRules($metricId);

        foreach ($items as &$item) {
            $value = (float) ($item['col_value'] ?? 0);
            $fillDate = (string) ($item['fill_date'] ?? '');

            $item['warning'] = $this->matchWarning($value, $rules, $fillDate, $birthDate, $gender);
        }

        return $items;
    }

    /**
     * 为监测趋势数据批量附加预警信息 + 同批次指标
     *
     * 内部完成：附加预警 → 附加同批次指标及预警
     *
     * @param array<int, array> $items 监测数据
     * @param string $empi 居民主索引
     * @param string $metricId 指标ID
     *
     * @return array<int, array>
     */
    public function attachBatchAndWarnings(array $items, string $empi, string $metricId): array
    {
        $items = $this->attachWarningToItems($items, $empi, $metricId);

        return $this->attachBatchMetricsToItems($items, $empi);
    }

    /**
     * 为监测趋势数据附加同批次指标
     *
     * 同一批次上传（相同 bsns_no）中的其他指标会作为 batch 数组附加到每条记录上，
     * batch 中的每条指标也会计算对应的预警信息。
     *
     * @param array<int, array> $items 监测数据（需包含 id、bsns_no 字段）
     * @param string $empi 居民主索引
     *
     * @return array<int, array>
     */
    public function attachBatchMetricsToItems(array $items, string $empi, bool $withWarning = true): array
    {
        $items = BetterArr::toArray($items);

        // 收集所有非空 bsns_no
        $bsnsNos = array_values(array_filter(array_unique(array_column($items, 'bsns_no'))));
        if (empty($bsnsNos)) {
            return $items;
        }

        // 获取数据库连接
        if (!empty($baselineDbConfig = config('low-code.bmo-baseline.database.default'))) {
            $connection = DBQuery::connection($baselineDbConfig)->getConnection()->table('personal_archive');
        } else {
            $connection = CrowdConnection::table('personal_archive');
        }

        $batchRecords = $connection
            ->whereIn('bsns_no', $bsnsNos)
            ->where('empi', $empi)
            ->select(['id', 'col_name', 'col_value', 'fill_date', 'bsns_no'])
            ->get()
            ->toArray();

        // 按 bsns_no 分组
        $grouped = [];
        foreach ($batchRecords as $record) {
            $record = (array) $record;
            $bsnsNo = $record['bsns_no'] ?? '';
            if (empty($bsnsNo)) {
                continue;
            }
            $grouped[$bsnsNo][] = $record;
        }

        // 获取患者人口学信息（一次）
        $residentBirthDate = null;
        $residentGender = null;
        $rules = [];

        if ($withWarning) {
            $demographic = $this->resolvePatientDemographic($empi);
            $residentBirthDate = $demographic['bth_dt'] ?? null;
            $residentGender = $demographic['gender'] ?? null;

            // 获取预警规则
            $distinctColNames = array_values(array_filter(array_unique(array_column($batchRecords, 'col_name'))));
            $rules = !empty($distinctColNames) ? $this->getVitalsWarningRules($distinctColNames) : [];
        }

        // 为每条主记录附加同批次指标
        foreach ($items as &$item) {
            $bsnsNo = $item['bsns_no'] ?? '';
            $itemId = $item['id'] ?? 0;

            if (empty($bsnsNo) || !isset($grouped[$bsnsNo])) {
                $item['batch'] = null;
                continue;
            }

            $batch = [];
            foreach ($grouped[$bsnsNo] as $sibling) {
                if (($sibling['id'] ?? 0) == $itemId) {
                    continue;
                }

                $colName = $sibling['col_name'] ?? '';
                $value = (float) ($sibling['col_value'] ?? 0);
                $fillDate = (string) ($sibling['fill_date'] ?? '');

                $warning = null;
                if ($withWarning) {
                    $warning = $this->matchFieldWarning(
                        $colName, $value, $rules,
                        $fillDate, $residentBirthDate, $residentGender
                    );
                }

                $batch[] = [
                    'id' => $sibling['id'] ?? 0,
                    'metric_id' => $colName,
                    'value' => (string) ($sibling['col_value'] ?? ''),
                    'warning' => $warning,
                ];
            }

            $item['batch'] = !empty($batch) ? $batch : null;
        }
        unset($item);

        return $items;
    }

    /**
     * 获取 监测指标趋势（分页）
     */
    public function getMonitorTrendList(
        string $empi,
        string $metricId,
        string|Carbon|null $minDate = null,
        string|Carbon|null $maxDate = null,
        string $sort = 'desc',
    ): mixed {
        $metricConfig = null;
        if (config('low-code.resident-archive.metric-from-upstream-enabled', false)) {
            // 获取上游指标
            $personalArchiveConfig = BmpCheetahMedicalCrowdkitApiService::make()->getPersonalArchiveConfig();
            $personalArchiveFields = array_column($personalArchiveConfig['data'] ?? [], null, 'src_col_name');

            $metricConfig = $personalArchiveFields[$metricId] ?? null;
        }

        return match (true) {
            // 存在指标配置时，为上游指标
            !is_null($metricConfig) => $this->getMonitorTrendListByUpstream(
                $empi, $metricId, $minDate, $maxDate, $metricConfig, $sort
            ),
            default => $this->getMonitorTrendListByBusiness(
                $empi, $metricId, $minDate, $maxDate, $sort
            ),
        };
    }

    /**
     * 获取单条监测指标详情
     *
     * @param int $id 指标记录ID
     *
     * @return array<string, mixed>|null
     */
    public function getMonitorTrendDetail(int $id): ?array
    {
        if (!empty($baselineDbConfig = config('low-code.bmo-baseline.database.default'))) {
            $connection = DBQuery::connection($baselineDbConfig)->getConnection()->table('personal_archive');
        } else {
            $connection = CrowdConnection::table('personal_archive');
        }

        $record = $connection
            ->where('id', $id)
            ->select(['id', 'col_value', 'fill_date', 'data_source', 'bsns_no', 'col_name', 'empi'])
            ->first();

        return $record ? (array) $record : null;
    }

    /**
     * 获取来自业务的监测指标趋势（分页）
     */
    public function getMonitorTrendListByBusiness(
        string $empi,
        string $metricId,
        string|Carbon|null $minDate = null,
        string|Carbon|null $maxDate = null,
        string $sort = 'desc',
    ): mixed {
        $connection = null;

        // 优先使用内置连接
        if (!empty($baselineDbConfig = config('low-code.bmo-baseline.database.default'))) {
            $connection = DBQuery::connection($baselineDbConfig)->getConnection()->table('personal_archive');
        } else {
            $connection = CrowdConnection::table('personal_archive');
        }

        return $connection
            ->where('col_name', $metricId)
            ->where('empi', $empi)
            ->whereBetweenDate('fill_date', $minDate, $maxDate, forceFullDay: true)
            ->select(['id', 'col_value', 'fill_date', 'data_source', 'bsns_no'])
            ->orderBy('fill_date', $sort)
            ->customPaginate(true);
    }

    /**
     * 获取来自上游的监测指标趋势（分页）
     */
    public function getMonitorTrendListByUpstream(
        string $empi,
        string $metricId,
        string|Carbon|null $minDate,
        string|Carbon|null $maxDate,
        array $metricConfig,
        string $sort = 'desc',
    ): mixed {
        // 根据empi获取身份证号
        $cardNo = null;
        if (!empty($psnTable = config('low-code.bmo-baseline.database.crowd-psn-wdth-table'))) {
            $cardNo = Cache::remember(
                'resident:' . md5('resolve_card_no:' . $psnTable . $empi),
                60 * 30,
                fn () => CrowdConnection::table($psnTable)->where('empi', $empi)->value('id_crd_no')
            );
        }

        if (empty($cardNo)) {
            return CrowdConnection::table($metricConfig['tbl_name'])->whereRaw('1 = 0')->customPaginate(true);
        }

        // 业务时间字段(用于排序及区间查询)
        $businessDateField = $metricConfig['time_col'] ?? 'upd_tm';

        // 目标字段
        $columnName = $metricConfig['tgt_col_name'];

        if (0 == $metricConfig['is_vertical']) {
            return CrowdConnection::table($metricConfig['tbl_name'])
                ->where('id_crd_no', $cardNo)
                ->when(!empty($minDate) && !empty($maxDate), fn ($query) => $query
                    ->whereBetween($businessDateField, [Carbon::make($minDate)->startOfDay(), Carbon::make($maxDate)->endOfDay()])
                )
                ->select(["{$businessDateField} as fill_date", "{$columnName} as col_value"])
                ->orderBy($businessDateField, $sort)
                ->customPaginate(true);
        }

        return CrowdConnection::table($metricConfig['tbl_name'])
            ->where('id_crd_no', $cardNo)
            ->where('item_name', $columnName)
            ->when(!empty($minDate) && !empty($maxDate), fn ($query) => $query
                ->whereBetween($businessDateField, [Carbon::make($minDate)->startOfDay(), Carbon::make($maxDate)->endOfDay()])
            )
            ->select(["{$businessDateField} as fill_date", 'item_value as col_value'])
            ->orderBy($businessDateField, $sort)
            ->customPaginate(true);
    }
}
