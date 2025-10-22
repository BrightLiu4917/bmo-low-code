<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Middleware;

use App\Models\LowCode\ApiAccount;
use BrightLiu\LowCode\Context\AdminContext;
use BrightLiu\LowCode\Context\OrgContext;
use BrightLiu\LowCode\Enums\Foundation\Logger;
use BrightLiu\LowCode\Enums\HeaderEnum;
use BrightLiu\LowCode\Exceptions\AuthenticateException;
use BrightLiu\LowCode\Services\BmoAuthApiService;
use BrightLiu\LowCode\Context\AuthContext;
use BrightLiu\LowCode\Context\DiseaseContext;
use Gupo\BetterLaravel\Http\Traits\HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 病种操作认证
 */
final class DiseaseAuthenticate
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
            //获取用户中心账号信息
            $bmoAccount = BmoAuthApiService::instance()->getUserInfoByToken($token);
            if (empty($bmoAccount)){
                throw new AuthenticateException('BmoAuth Account invalid.');
            }

            // 初始化上下文
            $this->autoContext($bmoAccount);
            $bmoApiAccount = new ApiAccount($bmoAccount);
            auth()->setUser($bmoApiAccount);
        } catch (\Throwable $e) {
            Logger::AUTHING->error(
                sprintf('DiseaseAuthenticate failed: %s', $e->getMessage()),
                ['headers' => $request->header()]
            );
            throw new AuthenticateException($e->getMessage());
        }

        return $next($request);
    }

    protected function autoContext(array $admin): void
    {
        $request = request();
        DiseaseContext::init(
            diseaseCode: (string) $request->header(HeaderEnum::DISEASE_CODE, ''),
        );

        OrgContext::init(
            orgCode: (string) $request->header(HeaderEnum::ORG_ID, ''),
        );

        AuthContext::init(
            systemCode: (string) $request->header(HeaderEnum::SYSTEM_CODE, ''),
            orgId: (int) $request->header(HeaderEnum::ORG_ID, ''),
            token: (string) $request->header(HeaderEnum::AUTHORIZATION, ''),
            requestSource: (string) $request->header(HeaderEnum::REQUEST_SOURCE, '')
        );

        AdminContext::init($admin);
    }
}
