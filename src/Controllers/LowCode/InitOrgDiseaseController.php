<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Controllers\LowCode;

use BrightLiu\LowCode\Services\LowCode\InitOrgDiseaseService;
use Gupo\BetterLaravel\Http\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 初始化机构病种
 */
final class InitOrgDiseaseController extends BaseController
{
    public function __invoke(Request $request, InitOrgDiseaseService $srv): JsonResponse
    {
        $tableName = (string) $request->input('table_name', '');

        $force = (bool) $request->input('force', true);

        $srv->handle($tableName, force: $force);

        return $this->responseSuccess();
    }
}
