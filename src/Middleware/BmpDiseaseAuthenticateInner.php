<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Middleware;

use BrightLiu\LowCode\Enums\HeaderEnum;
use BrightLiu\LowCode\Context\AuthContext;
use BrightLiu\LowCode\Context\DiseaseContext;
use Gupo\BetterLaravel\Http\Traits\HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use BrightLiu\LowCode\Context\OrgContext;

/**
 * 业务中台:病种操作认证(Inner)
 */
final class BmpDiseaseAuthenticateInner
{
    use HttpResponse;

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function handle($request, \Closure $next)
    {
        if (!empty(config('business.bmp-service.custom.disease_code'))) {
            $request->headers->set(HeaderEnum::DISEASE_CODE, (string) config('business.bmp-service.custom.disease_code'));
        }

        if (!empty(config('business.bmp-service.custom.system_code'))) {
            $request->headers->set(HeaderEnum::SYSTEM_CODE, (string) config('business.bmp-service.custom.system_code'));
        }

        if (!empty(config('business.bmp-service.custom.org_id'))) {
            $request->headers->set(HeaderEnum::ORG_ID, (string) config('business.bmp-service.custom.org_id'));
        }

        // 初始化上下文
        $this->autoContext();

        return $next($request);
    }

    /**
     * 初始化上下文
     */
    protected function autoContext(): void
    {
        $request = request();

        DiseaseContext::init(
            diseaseCode: (string) $request->header(HeaderEnum::DISEASE_CODE, (string) $request->input('disease_code', '')),
        );

        OrgContext::init(
            (string) $request->header(HeaderEnum::ORG_ID, $request->input('org_code', ''))
        );

        AuthContext::init(
            systemCode: (string) $request->header(HeaderEnum::SYSTEM_CODE, (string) $request->input('system_code', '')),
            orgId: 0,
            token: (string) $request->header(HeaderEnum::AUTHORIZATION),
            requestSource: (string) $request->header(HeaderEnum::REQUEST_SOURCE, (string) $request->input('request_source', ''))
        );
    }
}
