<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Controllers\LowCode;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Gupo\BetterLaravel\Http\BaseController;
use BrightLiu\LowCode\Services\RegionService;
use BrightLiu\LowCode\Services\LowCode\InitOrgDiseaseService;

/**
 * @Class
 * @Description:
 * @created: 2025-10-30 14:59:51
 * @modifier: 2025-10-30 14:59:51
 */
final class RegionController extends BaseController
{
    public function getRegionList(Request $request): JsonResponse
    {
        $usePermission = $request->input('use_permission', null);
        $data          = RegionService::instance()
            ->getRegionDataByConfigRegionCode(usePermission: $usePermission);
        return $this->responseSuccess('', $data);
    }
}
