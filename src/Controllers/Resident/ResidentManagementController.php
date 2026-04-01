<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Controllers\Resident;

use BrightLiu\LowCode\Requests\Resident\ResidentManagement\PreRequest;
use BrightLiu\LowCode\Resources\Resident\ResidentManagement\PreResource;
use Gupo\BetterLaravel\Http\BaseController;
use Illuminate\Http\JsonResponse;

/**
 * 居民纳管
 */
final class ResidentManagementController extends BaseController
{
    /**
     * 前置校验
     */
    public function pre(PreRequest $request): JsonResponse
    {
        $userId = (string) $request->input('user_id');

        // TODO: 待实现
        $result['checked'] = true;

        return $this->responseData($result, PreResource::class);
    }
}
