<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Middleware;

use BrightLiu\LowCode\Enums\HeaderEnum;
use BrightLiu\LowCode\Context\AuthContext;
use BrightLiu\LowCode\Context\DiseaseContext;
use BrightLiu\LowCode\Context\OrgContext;

/**
 * 业务中台:病种操作认证
 */
class BmpDiseaseAuthenticate
{
    /**
     * 初始化上下文
     */
    protected function autoContext(): void
    {
        $request = request();

        $request->headers->set(HeaderEnum::DISEASE_CODE, (string) config('business.bmp-service.custom.disease_code'));

        $request->headers->set(HeaderEnum::SYSTEM_CODE, (string) config('business.bmp-service.custom.system_code'));

        $request->headers->set(HeaderEnum::ORG_ID, (string) config('business.bmp-service.custom.org_id'));



        DiseaseContext::init(
            diseaseCode: (string) $request->header(HeaderEnum::DISEASE_CODE, $request->input('disease_code', '')),
        );

        OrgContext::init(
            (string) $request->header(HeaderEnum::ORG_ID, $request->input('org_code', ''))
        );

        AuthContext::init(
            systemCode: (string) $request->header(HeaderEnum::SYSTEM_CODE, $request->input('sys_code', '')),
            orgId: 0,
            token: (string) $request->header(HeaderEnum::AUTHORIZATION, ''),
            requestSource: (string) $request->header(HeaderEnum::REQUEST_SOURCE, '')
        );
    }
}
