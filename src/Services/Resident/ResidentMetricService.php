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
use Gupo\DBQuery\DBQuery;
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
        string|Carbon|null $minDate = null,
        string|Carbon|null $maxDate = null,
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
                ->when(!empty($minDate) && !empty($maxDate), fn ($query) => $query
                    ->whereBetween($businessDateField, [Carbon::make($minDate)->startOfDay(), Carbon::make($maxDate)->endOfDay()])
                )
                ->when($limit > 0, fn ($query) => $query->limit($limit))
                ->get(["{$businessDateField} as fill_date", "{$columnName} as col_value"])
                ->sortBy($businessDateField)
                ->toArray();
        } else {
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
    }
}
