<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\Resident;

use BrightLiu\LowCode\Models\Resident\ResidentMonitorMetric;
use BrightLiu\LowCode\Services\BmpCheetahMedicalCrowdkitApiService;
use BrightLiu\LowCode\Tools\Clock;
use BrightLiu\LowCode\Traits\Context\WithContext;
use Gupo\BetterLaravel\Service\BaseService;
use Illuminate\Support\Facades\DB;

/**
 * 居民监测指标相关
 */
class ResidentMetricService extends BaseService
{
    use WithContext;

    /**
     * 保存监测指标
     *
     * @param string $userId 居民主索引
     * @param array $metricIds 指标ID
     */
    public function saveMonitor(string $userId, array $metricIds = []): bool
    {
        $optionalMetrics = BmpCheetahMedicalCrowdkitApiService::make()->getMetricOptional();
        $optionalMetricMap = array_column($optionalMetrics, 'field_name', 'field');

        // TODO: 写法待完善
        DB::transaction(function () use ($userId, $metricIds, $optionalMetricMap) {
            ResidentMonitorMetric::query()
                ->where('disease_code', $this->getDiseaseCode())
                ->where('resident_user_id', $userId)
                ->delete();

            if (!empty($metricIds)) {
                ResidentMonitorMetric::query()->insert(
                    array_values(array_filter(
                        array_map(
                            fn ($metricId) => [
                                'disease_code' => $this->getDiseaseCode(),
                                'resident_user_id' => $userId,
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
}
