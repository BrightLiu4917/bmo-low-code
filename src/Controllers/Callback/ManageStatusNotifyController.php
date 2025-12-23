<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Controllers\Callback;

use BrightLiu\LowCode\Context\DiseaseContext;
use BrightLiu\LowCode\Context\OrgContext;
use BrightLiu\LowCode\Events\Callback\ManageStatusChanged;
use Gupo\BetterLaravel\Http\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 纳管状态变更通知
 */
final class ManageStatusNotifyController extends BaseController
{
    public function __invoke(Request $request): JsonResponse
    {
        $orgId = (int) $request->input('org_id', 0);

        $diseaseCode = (string) $request->input('disease_code', '');

        $sceneCode = (string) $request->input('scene_code', '');

        $userId = (string) $request->input('user_id', '');

        $manageStatus = (int) $request->input('manage_status', 0);

        $operatorName = (string) $request->input('operator_name', '');

        $operatorId = (string) $request->input('operator_id', '');

        $arcCode = (string) $request->input('arc_code', '');

        if (empty($userId) || empty($diseaseCode) || empty($sceneCode)) {
            return $this->responseError(message: '参数错误');
        }

        OrgContext::init((string) $orgId, $arcCode);

        DiseaseContext::init($diseaseCode, $sceneCode);

        event(new ManageStatusChanged(
            orgId: $orgId,
            diseaseCode: $diseaseCode,
            sceneCode: $sceneCode,
            userId: $userId,
            manageStatus: $manageStatus,
            operatorName: $operatorName,
            operatorId: $operatorId,
            arcCode: $arcCode,
        ));

        return $this->responseSuccess();
    }
}
