<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Controllers\Resident;

use BrightLiu\LowCode\Requests\Resident\ResidentMaintenance\CreateRequest;
use BrightLiu\LowCode\Requests\Resident\ResidentMaintenance\FileImportRequest;
use Gupo\BetterLaravel\Exceptions\ServiceException;
use Gupo\BetterLaravel\Http\BaseController;
use Illuminate\Http\JsonResponse;
use BrightLiu\LowCode\Services\Resident\ResidentService;
use BrightLiu\LowCode\Services\Resident\ResidentMaintenanceService;

final class ResidentMaintenanceController extends BaseController
{
    /**
     * 新增
     */
    public function create(CreateRequest $request, ResidentMaintenanceService $srv): JsonResponse
    {
        $params = (array) $request->input('params', []);

        $srv->create($params);

        return $this->responseSuccess('新增成功');
    }

    /**
     * 导入
     */
    public function import(FileImportRequest $request, ResidentMaintenanceService $srv): JsonResponse
    {
        try {
            $srv->import($request->file('file'));
        } catch (ServiceException $e) {
            return $this->responseError('导入失败: ' . $e->getMessage());
        }

        return $this->responseSuccess('导入成功');
    }

    /**
     * 获取导入模板
     */
    public function getImportTemplate(ResidentMaintenanceService $srv)
    {
        $outputPath = $srv->buildImportTemplateFile();

        $dateNo = date('Ymd');

        return response()->download($outputPath, "患者批量导入模板{$dateNo}.xlsx");
    }
}
