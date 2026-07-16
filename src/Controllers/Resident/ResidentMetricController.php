<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Controllers\Resident;

use BrightLiu\LowCode\Context\DiseaseContext;
use BrightLiu\LowCode\Models\Resident\ResidentMonitorMetric;
use BrightLiu\LowCode\Requests\Resident\ResidentMetric\MonitorListRequest;
use BrightLiu\LowCode\Requests\Resident\ResidentMetric\MonitorTrendCountRequest;
use BrightLiu\LowCode\Requests\Resident\ResidentMetric\MonitorTrendDetailsRequest;
use BrightLiu\LowCode\Requests\Resident\ResidentMetric\MonitorTrendItemsRequest;
use BrightLiu\LowCode\Requests\Resident\ResidentMetric\MonitorTrendListRequest;
use BrightLiu\LowCode\Requests\Resident\ResidentMetric\SaveMonitorRequest;
use BrightLiu\LowCode\Resources\Resident\ResidentMetric\MonitorListResource;
use BrightLiu\LowCode\Resources\Resident\ResidentMetric\MonitorTrendDetailsResource;
use BrightLiu\LowCode\Resources\Resident\ResidentMetric\MonitorTrendItemsResource;
use BrightLiu\LowCode\Resources\Resident\ResidentMetric\MonitorTrendListResource;
use BrightLiu\LowCode\Resources\Resident\ResidentMetric\OptionalResource;
use BrightLiu\LowCode\Services\BmpCheetahMedicalCrowdkitApiService;
use BrightLiu\LowCode\Services\BmpCheetahMedicalPlatformApiService;
use BrightLiu\LowCode\Services\Resident\ResidentMetricService;
use BrightLiu\LowCode\Tools\BetterArr;
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

        $srv = ResidentMetricService::make();

        $data = ResidentMonitorMetric::query()
            ->byContextDisease()
            ->where('scene_code', DiseaseContext::instance()->getSceneCode())
            ->where('resident_empi', $empi)
            ->orderBy('id')
            ->get();

        // TODO: 这样实现有缺陷，无法完全去除监测指标，始终会有
        if ($data->isEmpty()) {
            $archiveTrendConfig = rescue(
                fn () => BmpCheetahMedicalPlatformApiService::make()->getArchiveTrendConfig(),
                []
            );
            $data = $srv->buildMonitorListFromArchiveTrendConfig($archiveTrendConfig);
        } else {
            $srv->enrichMonitorListWithGroupName($data);
        }

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

        // 是否附加预警信息
        $withWarning = (bool) $request->input('with_warning', false);

        try {
            $srv = ResidentMetricService::make();

            $data = $srv->getMonitorTrendItems(
                empi: $empi,
                metricId: $metricId,
                minDate: $dateRangeMin,
                maxDate: $dateRangeMax,
                limit: $limit
            );

            $data = BetterArr::toArray($data);

            // 附加预警信息
            if ($withWarning) {
                $data = $srv->attachWarningToItems($data, $empi, $metricId);
            }
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
     * 监测指标趋势（分页）
     */
    public function monitorTrendList(MonitorTrendListRequest $request): JsonResponse
    {
        // 居民主索引
        $empi = (string) $request->input('empi', '');

        // 指标ID
        $metricId = (string) $request->input('metric_id', '');

        // 时间范围-开始
        $dateRangeMin = (string) $request->input('date_range.0', '');

        // 时间范围-截至
        $dateRangeMax = (string) $request->input('date_range.1', '');

        // 排序方式：asc 升序、desc 降序，默认降序
        $sort = (string) $request->input('sort', 'desc');

        // 是否获取同批次指标
        $withBatch = (bool) $request->input('with_batch', false);

        // 是否获取预警信息
        $withWarning = (bool) $request->input('with_warning', false);

        try {
            $srv = ResidentMetricService::make();

            // 获取趋势分页数据（现有逻辑）
            $data = $srv->getMonitorTrendList(
                empi: $empi,
                metricId: $metricId,
                minDate: $dateRangeMin,
                maxDate: $dateRangeMax,
                sort: $sort,
            );

            $items = BetterArr::toArray($data->getCollection());

            if ($withWarning) {
                $items = $srv->attachWarningToItems($items, $empi, $metricId);
            }

            if ($withBatch) {
                $items = $srv->attachBatchMetricsToItems($items, $empi, $withWarning);
            }

            $data->setCollection(collect($items));
        } catch (\Throwable $e) {
            logs()->error('获取居民监测指标趋势分页失败', [
                'empi' => $empi,
                'metric_id' => $metricId,
                'date_range_min' => $dateRangeMin,
                'date_range_max' => $dateRangeMax,
                'error_msg' => $e->getMessage(),
            ]);

            return $this->responseError('获取居民监测指标趋势分页失败');
        }

        return $this->responseData($data, MonitorTrendListResource::class);
    }

    /**
     * 监测指标趋势详情
     */
    public function monitorTrendDetails(MonitorTrendDetailsRequest $request): JsonResponse
    {
        $id = (int) $request->input('id', 0);

        // 是否获取同批次指标
        $withBatch = (bool) $request->input('with_batch', false);

        // 是否获取预警信息
        $withWarning = (bool) $request->input('with_warning', false);

        try {
            $srv = ResidentMetricService::make();

            $details = $srv->getMonitorTrendDetail($id);
            if (empty($details)) {
                return $this->responseError('指标记录不存在');
            }

            if ($withWarning) {
                $empi = (string) ($details['empi'] ?? '');
                $metricId = (string) ($details['col_name'] ?? '');
                [0 => $details] = $srv->attachWarningToItems([$details], $empi, $metricId);
            }

            if ($withBatch) {
                $empi = (string) ($details['empi'] ?? '');
                [0 => $details] = $srv->attachBatchMetricsToItems([$details], $empi, $withWarning);
            }
        } catch (\Throwable $e) {
            logs()->error('获取居民监测指标趋势详情失败', [
                'id' => $id,
                'error_msg' => $e->getMessage(),
            ]);

            return $this->responseError('获取居民监测指标趋势详情失败');
        }

        return $this->responseData($details, MonitorTrendDetailsResource::class);
    }

    /**
     * 监测指标趋势数量统计
     */
    public function monitorTrendCount(MonitorTrendCountRequest $request): JsonResponse
    {
        // 居民主索引
        $empi = (string) $request->input('empi', '');

        // 指标ID
        $metricId = (string) $request->input('metric_id', '');

        // 时间范围-开始
        $dateRangeMin = (string) $request->input('date_range.0', '');

        // 时间范围-截至
        $dateRangeMax = (string) $request->input('date_range.1', '');

        try {
            $total = ResidentMetricService::make()->getMonitorTrendCount(
                empi: $empi,
                metricId: $metricId,
                minDate: $dateRangeMin,
                maxDate: $dateRangeMax,
            );
        } catch (\Throwable $e) {
            logs()->error('获取居民监测指标趋势数量统计失败', [
                'empi' => $empi,
                'metric_id' => $metricId,
                'date_range_min' => $dateRangeMin,
                'date_range_max' => $dateRangeMax,
                'error_msg' => $e->getMessage(),
            ]);

            return $this->responseError('获取居民监测指标趋势数量统计失败');
        }

        return $this->responseData(['total' => $total]);
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
