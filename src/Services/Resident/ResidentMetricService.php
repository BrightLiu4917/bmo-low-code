<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\Resident;

use BrightLiu\LowCode\Models\Resident\ResidentMonitorMetric;
use BrightLiu\LowCode\Services\BmpCheetahMedicalCrowdkitApiService;
use BrightLiu\LowCode\Support\CrowdConnection;
use BrightLiu\LowCode\Tools\Clock;
use BrightLiu\LowCode\Traits\Context\WithContext;
use Carbon\Carbon;
use Gupo\BetterLaravel\Service\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * 居民监测指标相关
 */
class ResidentMetricService extends BaseService
{
    use WithContext;

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
                ->where('resident_empi', $empi)
                ->delete();

            if (!empty($metricIds)) {
                ResidentMonitorMetric::query()->insert(
                    array_values(array_filter(
                        array_map(
                            fn ($metricId) => [
                                'disease_code' => $this->getDiseaseCode(),
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
        // TODO: 可适当缓存
        // 获取上游指标
        $personalArchiveConfig = BmpCheetahMedicalCrowdkitApiService::make()->getPersonalArchiveConfig();
        $personalArchiveFields = array_column($personalArchiveConfig['data'] ?? [], null, 'src_col_name');

        $metricConfig = $personalArchiveFields[$metricId] ?? null;

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
     * 获取来自业务的监测指标趋势
     */
    public function getMonitorTrendItemsByBusiness(
        string $empi,
        string $metricId,
        string|Carbon|null $minDate = null,
        string|Carbon|null $maxDate = null,
        int $limit = 0
    ): array {
        return CrowdConnection::table('personal_archive')
            ->where('tenant_id', $this->getTenantId())
            ->where('col_name', $metricId)
            ->where('disease_code', $this->getDiseaseCode())
            ->where('sys_code', $this->getSystemCode())
            ->where('org_code', $this->getOrgCode())
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
        string|Carbon|null $minDate = null,
        string|Carbon|null $maxDate = null,
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
                ->whereBetweenDate($businessDateField, $minDate, $maxDate, forceFullDay: true)
                ->when($limit > 0, fn ($query) => $query->limit($limit))
                ->get(["{$businessDateField} as fill_date", "{$columnName} as col_value"])
                ->sortBy($businessDateField)
                ->toArray();
        } else {
            return CrowdConnection::table($metricConfig['tbl_name'])
                ->where('id_crd_no', $cardNo)
                ->where('item_name', $columnName)
                ->whereBetweenDate($businessDateField, $minDate, $maxDate, forceFullDay: true)
                ->when($limit > 0, fn ($query) => $query->limit($limit))
                ->get(["{$businessDateField} as fill_date", 'item_value as col_value'])
                ->sortBy($businessDateField)
                ->toArray();
        }
    }
}
