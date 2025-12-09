<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Controllers\LowCode;

use BrightLiu\LowCode\Requests\Foundation\PersonalizeMenu\SaveRequest;
use BrightLiu\LowCode\Resources\PersonalizeMenu\ListResource;
use BrightLiu\LowCode\Resources\PersonalizeMenu\RoutesResource;
use BrightLiu\LowCode\Models\LowCodePersonalizeModule;
use BrightLiu\LowCode\Services\LowCode\LowCodePersonalizeModuleService;
use Gupo\BetterLaravel\Http\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 个性化模块
 */
final class LowCodePersonalizeModuleController extends BaseController
{
    /**
     * 列表
     */
    public function list(Request $request): JsonResponse
    {
        $moduleType = (string) $request->input('module_type', 'crowd_patients');

        $data = LowCodePersonalizeModule::query()
                                        ->byContextDisease()
                                        ->where('module_type', $moduleType)
                                        ->orderByDesc('weight')
                                        ->get(['id', 'title', 'module_id', 'module_type', 'metadata', 'created_at']);

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
                                        ->where('module_type', $moduleType)
                                        ->orderByDesc('weight')
                                        ->get(['id', 'title', 'module_id', 'module_type', 'metadata', 'created_at']);

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
}

