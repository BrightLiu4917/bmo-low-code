<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Controllers\Resident;

use BrightLiu\LowCode\Models\Resident\ResidentMonitorMetric;
use BrightLiu\LowCode\Requests\Resident\ResidentMetric\MonitorListRequest;
use BrightLiu\LowCode\Requests\Resident\ResidentMetric\MonitorTrendItemsRequest;
use BrightLiu\LowCode\Requests\Resident\ResidentMetric\SaveMonitorRequest;
use BrightLiu\LowCode\Resources\Resident\ResidentMetric\MonitorListResource;
use BrightLiu\LowCode\Resources\Resident\ResidentMetric\MonitorTrendItemsResource;
use BrightLiu\LowCode\Resources\Resident\ResidentMetric\OptionalResource;
use BrightLiu\LowCode\Services\BmpCheetahMedicalCrowdkitApiService;
use BrightLiu\LowCode\Services\Resident\ResidentMetricService;
use BrightLiu\LowCode\Traits\Context\WithAuthContext;
use BrightLiu\LowCode\Traits\Context\WithDiseaseContext;
use BrightLiu\LowCode\Traits\Context\WithOrgContext;
use Gupo\BetterLaravel\Http\BaseController;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;

/**
 * 居民指标
 */
class ResidentMetricController extends BaseController
{
    use WithAuthContext;
    use WithDiseaseContext;
    use WithOrgContext;

    /**
     * 可选指标
     *
     * @throws BindingResolutionException
     */
    public function optional(): JsonResponse
    {
        $items = BmpCheetahMedicalCrowdkitApiService::make()->getMetricOptional();

        return $this->responseData([
            'items' => OptionalResource::collection($items),
        ]);
    }

    /**
     * 监测指标列表
     */
    public function monitorList(MonitorListRequest $request): JsonResponse
    {
        $empi = (string) $request->input('empi', '');

        $data = ResidentMonitorMetric::query()
            ->byContextDisease()
            ->where('resident_empi', $empi)
            ->orderBy('id')
            ->get();

        return $this->responseData([
            'items' => MonitorListResource::collection($data),
        ]);
    }

    /**
     * 监测指标趋势
     */
    public function monitorTrendItems(MonitorTrendItemsRequest $request): JsonResponse
    {
        // 居民主索引
        $empi = (string) $request->input('empi', '');

        // 指标ID
        $metricId = (string) $request->input('metric_id', '');

        // 时间范围-开始
        $dateRangeMin = (string) $request->input('date_range.0', '');

        // 时间范围-截至
        $dateRangeMax = (string) $request->input('date_range.1', '');

        // 限制条数
        $limit = (int) $request->input('limit', 0);

        try {
            $data = ResidentMetricService::make()->getMonitorTrendItems(
                empi: $empi,
                metricId: $metricId,
                minDate: $dateRangeMin,
                maxDate: $dateRangeMax,
                limit: $limit
            );
        } catch (\Throwable $e) {
            logs()->error('获取居民监测指标趋势失败', [
                'empi' => $empi,
                'metric_id' => $metricId,
                'date_range_min' => $dateRangeMin,
                'date_range_max' => $dateRangeMax,
                'limit' => $limit,
                'error_msg' => $e->getMessage(),
            ]);

            return $this->responseError('获取居民监测指标趋势失败');
        }

        return $this->responseData([
            'items' => MonitorTrendItemsResource::collection($data),
        ]);
    }

    /**
     * 保存监测指标项
     */
    public function saveMonitor(SaveMonitorRequest $request, ResidentMetricService $srv): JsonResponse
    {
        $empi = (string) $request->input('empi', '');

        $metricIds = (array) $request->input('metric_ids', []);

        // TODO: 判断居民、指标是否存在

        $srv->saveMonitor($empi, $metricIds);

        return $this->responseSuccess();
    }
}
