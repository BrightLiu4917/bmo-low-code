<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Middleware;

use BrightLiu\LowCode\Context\AdminContext;
use BrightLiu\LowCode\Context\OrgContext;
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
            $bmoAccount = BmoAuthApiService::instance()->getUserInfoByToken($token);
            if (empty($bmoAccount)){
                throw new AuthenticateException('BmoAuth Account invalid.');
            }

            // 初始化上下文
            $this->autoContext($bmoAccount,$token);
        } catch (\Throwable $e) {
            Logger::AUTHING->error(
                sprintf('DiseaseAuthenticate failed: %s', $e->getMessage()),
                ['headers' => $request->header()]
            );
            throw new AuthenticateException($e->getMessage());
        }

        return $next($request);
    }

    protected function autoContext(array $admin,string $token = ''): void
    {
        $request = request();
        DiseaseContext::init(
            diseaseCode: (string) $request->header(HeaderEnum::DISEASE_CODE, $request->input('disease_code', '')),
            sceneCode: (string) $request->header(HeaderEnum::SCENE_CODE, $request->input('scene_code', ''))
        );

        OrgContext::init(
            orgCode: (string)$request->header(
                HeaderEnum::ORG_ID,
                $request->input('org_code', '')
            ),
            arcCode: (string)$request->header(
                HeaderEnum::ARC_CODE,
                $request->input('arc_code', '')
            ),
            token: $token
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
