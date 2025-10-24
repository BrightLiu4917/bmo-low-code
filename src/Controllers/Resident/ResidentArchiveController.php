<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Controllers\Resident;

use BrightLiu\LowCode\Requests\Resident\ResidentArchive\ResidentArchiveRequest;
use BrightLiu\LowCode\Resources\Resident\ResidentArchive\BasicInfoResource;
use BrightLiu\LowCode\Resources\Resident\ResidentArchive\InfoResource;
use BrightLiu\LowCode\Services\CrowdKitService;
use BrightLiu\LowCode\Services\Resident\FollowResidentService;
use BrightLiu\LowCode\Services\Resident\ResidentArchiveService;
use BrightLiu\LowCode\Services\Resident\TestingResidentService;
use Gupo\BetterLaravel\Http\BaseController;
use Illuminate\Http\JsonResponse;

/**
 * 居民档案
 */
class ResidentArchiveController extends BaseController
{
    /**
     * 获取健康档案信息
     */
    public function info(ResidentArchiveRequest $request, ResidentArchiveService $srv, CrowdKitService $kitSrv): JsonResponse
    {
        $empi = (string) $request->input('empi');

        $columnGroup = $kitSrv->getOptionalColumns();

        $attributes = $srv->getInfo($empi);

        $data = $kitSrv->combineColumnGroup($columnGroup, $attributes);

        return $this->responseData($data, class_map(InfoResource::class));
    }

    /**
     * 更新健康档案信息
     */
    public function updateInfo(ResidentArchiveRequest $request, ResidentArchiveService $srv): JsonResponse
    {
        $empi = (string) $request->input('empi');

        $attributes = (array) $request->input('attributes', []);

        $srv->updateInfo($empi, $attributes);

        return $this->responseSuccess();
    }

    /**
     * 获取基本信息
     */
    public function basicInfo(ResidentArchiveRequest $request, ResidentArchiveService $srv): JsonResponse
    {
        $empi = (string) $request->input('empi');

        $data = $srv->getBasicInfo($empi);

        return $this->responseData($data, BasicInfoResource::class);
    }

    /**
     * 重点关注
     */
    public function follow(ResidentArchiveRequest $request, FollowResidentService $srv): JsonResponse
    {
        $empi = (string) $request->input('empi');

        $srv->follow($empi);

        return $this->responseSuccess();
    }

    /**
     * 取消重点关注
     */
    public function unfollow(ResidentArchiveRequest $request, FollowResidentService $srv): JsonResponse
    {
        $empi = (string) $request->input('empi');

        $srv->unfollow($empi);

        return $this->responseSuccess();
    }

    /**
     * 标记为测试
     */
    public function maskTesting(ResidentArchiveRequest $request, TestingResidentService $srv): JsonResponse
    {
        $empi = (string) $request->input('empi');

        $srv->maskTesting($empi);

        return $this->responseSuccess();
    }

    /**
     * 取消标记为测试
     */
    public function unmaskTesting(ResidentArchiveRequest $request, TestingResidentService $srv): JsonResponse
    {
        $empi = (string) $request->input('empi');

        $srv->unmaskTesting($empi);

        return $this->responseSuccess();
    }
}
