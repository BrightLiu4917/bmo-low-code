<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Controllers\LowCode;

use BrightLiu\LowCode\Enums\Foundation\Logger;
use BrightLiu\LowCode\Resources\LowCode\OptionalResource;
use App\Services\Api\Bmp\BmpCheetahMedicalPlatformApiService;
use Gupo\BetterLaravel\Http\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ResidentCrowdController extends BaseController
{
    /**
     * 可选人群分类
     */
    public function optional(Request $request): JsonResponse
    {
        try {
            $data = BmpCheetahMedicalPlatformApiService::make()->getCrowds();
        } catch (\Throwable $e) {
            Logger::LARAVEL->error($e);
            $data = [];
        }

        return $this->responseData([
            'list' => OptionalResource::collection($data),
        ]);
    }
}
