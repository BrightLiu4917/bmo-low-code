<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Middleware;

use BrightLiu\LowCode\Context\AdminContext;
use BrightLiu\LowCode\Context\OrgContext;
use BrightLiu\LowCode\Services\RegionService;
use BrightLiu\LowCode\Enums\Foundation\Logger;
use BrightLiu\LowCode\Enums\HeaderEnum;
use BrightLiu\LowCode\Exceptions\AuthenticateException;
use BrightLiu\LowCode\Services\BmoAuthApiService;
use BrightLiu\LowCode\Context\AuthContext;
use BrightLiu\LowCode\Context\DiseaseContext;
use Gupo\BetterLaravel\Http\Traits\HttpResponse;


/**
 * 病种操作认证
 */
class DiseaseAuthenticate
{
    use HttpResponse;

    /**
     * @param          $request
     * @param \Closure $next
     *
     * @return mixed
     * @throws \BrightLiu\LowCode\Exceptions\AuthenticateException
     */
    public function handle($request, \Closure $next)
    {
        try {
            if (empty($token = $request->header(HeaderEnum::AUTHORIZATION, ''))) {
                throw new AuthenticateException('Token invalid.');
            }

            if (empty($arcCode = $request->header(HeaderEnum::ARC_CODE, ''))) {
                throw new AuthenticateException('x-gp-arc-code invalid.');
            }

            if (empty($request->header(HeaderEnum::SCENE_CODE, ''))) {
                throw new AuthenticateException('x-gp-scene_code invalid.');
            }

            if (empty($request->header(HeaderEnum::SYSTEM_CODE, ''))) {
                throw new AuthenticateException('x-gp-system_code invalid.');
            }

            if (empty($request->header(HeaderEnum::DISEASE_CODE, ''))) {
                throw new AuthenticateException('x-gp-disease_code invalid.');
            }

            //获取用户中心账号信息
            $bmoAccount = BmoAuthApiService::instance()->getUserByToken(token:$token,arcCode: $arcCode);
            if (empty($bmoAccount)){
                throw new AuthenticateException('BmoAuth Account invalid.');
            }


//            $rcUserId = data_get($bmoAccount, 'rc_user_id','');
//            if (empty($rcUserId)){
//                throw new AuthenticateException('当前登录账号未关联职工信息， 请在资源中心职工管理新增当前职工信息');
//            }

            $bmoAccountDataPermission = [];
            if (config('low-code.data-permission-enabled', true)) {
                if (empty($bmoAccountDataPermission = BmoAuthApiService::instance()->getArcDataPermissionByUserId(
                    userId: (int)$bmoAccount['id'],
                    arcCode: $arcCode)
                )) {
                    throw new AuthenticateException('BmoAuth Account Data Permission invalid.');
                }
            }

            // 初始化上下文
            $this->autoContext($bmoAccount,$token,$arcCode,$bmoAccountDataPermission);
        } catch (\Throwable $e) {
            Logger::AUTHING->error(
                sprintf('DiseaseAuthenticate failed: %s', $e->getMessage()),
                ['headers' => $request->header()]
            );
            throw new AuthenticateException($e->getMessage());
        }

        return $next($request);
    }

    protected function autoContext(array $admin,string $token = '',string $arcCode = '',array $bmoAccountDataPermission = []): void
    {
        $request = request();
        DiseaseContext::init(
            diseaseCode: (string) $request->header(HeaderEnum::DISEASE_CODE, $request->input('disease_code', '')),
            sceneCode: (string) $request->header(HeaderEnum::SCENE_CODE, $request->input('scene_code', ''))
        );

        $bmoManageAreaCodes = data_get($admin, 'org_extension.arc_manage_areas',[]);

        $manageAreaCodes = match (true) {
            !empty($bmoManageAreaCodes) => RegionService::instance()->getBatchRegionLevel($bmoManageAreaCodes),
            default => []
        };

        // 当未开启数据权限时，不做额外处理
        $dataPermissionManageAreaArr = [];
        if (empty($bmoAccountDataPermission)) {
            $dataPermissionManageAreaArr = data_get($bmoAccountDataPermission, 'manage_area_arr',[]);

            $dataPermissionManageAreaArr = match (true) {
                !empty($dataPermissionManageAreaArr) => RegionService::instance()->getBatchRegionLevel($dataPermissionManageAreaArr),
                default => []
            };
        }

        $manageOrgCodes = data_get($admin, 'org_extension.arc_manage_orgs',[]);


        $affiliatedOrgName = data_get($admin, 'org_extension.affiliated_org_name','');

        $affiliatedOrgCode = data_get($admin, 'org_extension.affiliated_org_code','');

        $rcUserId = data_get($admin, 'rc_user_id','');


        $dataPermissionManageOrgArr = data_get($bmoAccountDataPermission, 'manage_org_arr',[]);



        OrgContext::init(
            orgCode: (string)$request->header(
                HeaderEnum::ORG_ID,
                $request->input('org_code', '')
            ),
            arcCode: (string)$request->header(
                HeaderEnum::ARC_CODE,
                $request->input('arc_code', '')
            ),
            manageAreaCodes: $manageAreaCodes,
            manageOrgCodes: $manageOrgCodes,
            affiliatedOrgName: $affiliatedOrgName,
            affiliatedOrgCode: $affiliatedOrgCode,
            rcUserId: $rcUserId,
            dataPermissionManageAreaArr: $dataPermissionManageAreaArr,
            dataPermissionManageOrgArr: $dataPermissionManageOrgArr

        );

        AuthContext::init(
            systemCode: (string) $request->header(HeaderEnum::SYSTEM_CODE, $request->input('sys_code', '')),
            orgId: (int) $request->header(HeaderEnum::ORG_ID, $request->input('org_code', 0)),
            token: (string) $request->header(HeaderEnum::AUTHORIZATION, ''),
            requestSource: (string) $request->header(HeaderEnum::REQUEST_SOURCE, ''),
            arcCode: (string) $request->header(HeaderEnum::ARC_CODE, ''),
        );
        AdminContext::init($admin);
    }
}
