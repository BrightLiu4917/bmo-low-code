<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Controllers\LowCode;

use BrightLiu\LowCode\Models\LowCodeCrowdLayer;
use BrightLiu\LowCode\Requests\LowCode\LowCodeCrowdLayer\SaveRequest;
use BrightLiu\LowCode\Resources\LowCode\LowCodeCrowdLayer\ListResource;
use BrightLiu\LowCode\Services\LowCode\LowCodeCrowdLayerService;
use BrightLiu\LowCode\Services\LowCode\LowCodeListService;
use BrightLiu\LowCode\Services\LowCode\LowCodePersonalizeModuleService;
use BrightLiu\LowCode\Traits\Context\WithOrgContext;
use Gupo\BetterLaravel\Http\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LowCodeCrowdLayerController extends BaseController
{
    use WithOrgContext;

    /**
     * 列表
     */
    public function list(Request $request): JsonResponse
    {
        // 是否为显示模式
        // 显示模式下会在列表前面增加一个默认的“本机构患者”人群层
        $isDisplay = (string) $request->input('is_display', 0);

        $moduleId = (string) $request->input('module_id', '');

        $moduleType = (string) $request->input('module_type', 'personalize_module');

        $data = LowCodeCrowdLayer::query()
            ->byContextDisease()
            ->where('org_code', $this->getAffiliatedOrgCode())
            ->where('module_id', $moduleId)
            ->where('module_type', $moduleType)
            ->orderByDesc('weight')
            ->get(['id', 'title', 'module_id', 'crowd_id', 'created_at', 'preset_filters']);

        if ($isDisplay) {
            $data->prepend(new LowCodeCrowdLayer([
                'id' => 0,
                'title' => '本机构患者',
                'module_id' => $moduleId,
                'crowd_id' => '',
                'created_at' => now(),
                'preset_filters' => [],
            ]));
        }

        return $this->responseData([
            'list' => ListResource::collection($data),
        ]);
    }

    /**
     * 保存
     */
    public function save(SaveRequest $request, LowCodeCrowdLayerService $srv): JsonResponse
    {
        $moduleId = (string) $request->input('module_id', '');

        $moduleType = (string) $request->input('module_type', 'personalize_module');

        $items = (array) $request->input('items', []);

        $srv->save($moduleId, $moduleType, $items);

        return $this->responseSuccess();
    }

    /**
     * 统计数量
     */
    public function statistics(Request $request, LowCodeListService $srv): JsonResponse
    {
        $layerIds = (array) $request->input('layer_ids', []);

        $moduleId = (string) $request->input('module_id', '');

        $moduleType = (string) $request->input('module_type', 'personalize_module');

        // 获取模块(自定义菜单)关联的人群id
        $moduleCrowdId = 0;
        if (!empty($moduleId)) {
            $moduleCrowdId = LowCodePersonalizeModuleService::make()->getModuleCrowdId((int) $moduleId);
        }

        $result = [];
        if (!empty($layerIds)) {
            $searchLayerIds = array_values(array_unique(array_filter($layerIds)));

            // TODO: 目前仅支持居民人群，后续如果有其他人群类型需要统计，可以增加关联模型和条件
            $layers = collect();
            if (!empty($searchLayerIds)) {
                $layers = LowCodeCrowdLayer::query()
                    ->whereIn('id', $searchLayerIds)
                    ->with(['personalizeModule'])
                    ->get(['id', 'module_type', 'module_id', 'crowd_id', 'preset_filters'])
                    ->keyBy('id');
            }

            $result = array_map(function ($layerId) use ($srv, $layers, $moduleCrowdId) {
                $layer = $layers->get($layerId);

                $filters = [];
                if (!empty($layer->crowd_id) && !empty($layer->personalizeModule)) {
                    $filters = array_merge(
                        // 自定义菜单关联的人群ID
                        [['crowd_id', '=', $layer->personalizeModule->module_id]],

                        // 人群分层关联的人群ID
                        $layer->preset_filters ?? []
                    );
                } else {
                    $filters = array_merge(
                        // 自定义菜单关联的人群ID
                        !empty($moduleCrowdId) ? [['crowd_id', '=', $moduleCrowdId]] : [],
                    );
                }

                $count = 0;
                if (!($layerId > 0 && empty($layer))) {
                    $count = $srv->queryCount([['filters' => $filters]]);
                }

                return [
                    'layer_id' => $layerId,
                    'count' => $count,
                ];
            }, $layerIds);
        }

        return $this->responseData([
            'list' => $result,
        ]);
    }
}
