<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Controllers\LowCode;

use BrightLiu\LowCode\Requests\Foundation\PersonalizeMenu\SaveRequest;
use BrightLiu\LowCode\Resources\PersonalizeMenu\ListResource;
use BrightLiu\LowCode\Resources\PersonalizeMenu\RoutesResource;
use BrightLiu\LowCode\Models\LowCodePersonalizeModule;
use BrightLiu\LowCode\Services\LowCode\LowCodePersonalizeModuleService;
use BrightLiu\LowCode\Traits\Context\WithOrgContext;
use Gupo\BetterLaravel\Http\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 个性化模块
 */
final class LowCodePersonalizeModuleController extends BaseController
{
    use WithOrgContext;

    /**
     * 列表
     */
    public function list(Request $request): JsonResponse
    {
        $moduleType = (string) $request->input('module_type', 'crowd_patients');

        $data = LowCodePersonalizeModule::query()
                                        ->byContextDisease()
                                        ->byContextScene()
                                        ->where('org_code', $this->getAffiliatedOrgCode())
                                        ->where('module_type', $moduleType)
                                        ->orderByDesc('weight')
                                        ->get(['id', 'title', 'module_id', 'module_ids', 'module_meta', 'module_type', 'metadata', 'created_at']);

        return $this->responseData([
            'list' => ListResource::collection($data),
        ]);
    }

    /**
     * 路由
     */
    public function routes(Request $request): JsonResponse
    {
        $moduleType = (string) $request->input('module_type', 'crowd_patients');

        $data = LowCodePersonalizeModule::query()
                                        ->byContextDisease()
                                        ->byContextScene()
                                        ->where('org_code', $this->getAffiliatedOrgCode())
                                        ->where('module_type', $moduleType)
                                        ->orderByDesc('weight')
                                        ->get(['id', 'title', 'module_id', 'module_ids', 'module_meta', 'module_type', 'metadata', 'created_at']);

        return $this->responseData($data, RoutesResource::class);
    }

    /**
     * 保存
     */
    public function save(SaveRequest $request, LowCodePersonalizeModuleService $srv): JsonResponse
    {
        $items = (array) $request->input('items', []);

        $srv->save($items, defaultModuleType: 'crowd_patients');

        return $this->responseSuccess();
    }

    /**
     * 获取模块关联场景
     */
    public function relatedScenes(Request $request, LowCodePersonalizeModuleService $srv): JsonResponse
    {
        $id = (int) $request->input('id', 0);
        if ($id <= 0) {
            return $this->responseData(['list' => []]);
        }

        $module = LowCodePersonalizeModule::query()
            ->where('id', $id)
            ->first(['id', 'module_id', 'module_ids', 'module_meta', 'disease_code', 'scene_code', 'module_type']);

        if (!$module instanceof LowCodePersonalizeModule) {
            return $this->responseData(['list' => []]);
        }

        return $this->responseData([
            'list' => $srv->getRelatedScenes($module),
        ]);
    }
}

