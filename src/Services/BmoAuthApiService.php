<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Services;


use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use BrightLiu\LowCode\Enums\Foundation\Logger;

/**
 * @Class
 * @Description:
 * @created    : 2025-10-01 09:36:38
 * @modifier   : 2025-10-01 09:36:38
 */
class BmoAuthApiService extends LowCodeBaseService
{

    /**
     * @var string
     */
    protected string $baseUri = '';

    /**
     *
     */
    public function __construct()
    {
        $this->baseUri = config('business.bmo-service.auth.uri');
    }

    /**
     * @param  string  $token
     * @param  string  $arcCode
     *
     * @return array
     */
    public function getUserByToken(
        string $token = '',
        string $arcCode = '',
    ): array {
        $appId     = config('business.bmo-service.app_id');
        $appSecret = config('business.bmo-service.app_secret');
        if (!$appId || !$appSecret) {
            throw new \RuntimeException('BMO服务配置错误');
        }

        $response = Http::timeout(10)->asJson()->retry(2, 500) // 重试2次，间隔500毫秒

            ->withHeaders(
                [
                    'AppId'         => $appId,
                    'AppSecret'     => $appSecret,
                    'Authorization' => $token,
                ]
            )->get(
                $this->baseUri.'/innerapi/get-user-by-token',
                ['arc_code' => $arcCode]
            )->json();
        Logger::BMO_AUTH_DEBUG->info('获取用户中心数据', [
            'token_prefix' => substr($token, 0, 8).'...',
            'arc_code'     => $arcCode,
            'response'     => $response,
        ]);
        return $response['data'] ?? [];
    }



    public function getArcDataPermissionByUserId(
        string $userId = '',
        string $arcCode = '',
    ): array {
        $appId     = config('business.bmo-service.app_id');
        $appSecret = config('business.bmo-service.app_secret');
        if (!$appId || !$appSecret) {
            throw new \RuntimeException('BMO服务配置错误');
        }

        $response = Http::timeout(10)->asJson()->retry(2, 500) // 重试2次，间隔500毫秒

        ->withHeaders(
            [
                'AppId'         => $appId,
                'AppSecret'     => $appSecret,
            ]
        )->get(
            $this->baseUri.'/innerapi/arc/data-permission',
            ['arc_code' => $arcCode, 'user_id' => $userId]
        )->json();
        Logger::BMO_AUTH_DEBUG->info('获取该用户的权限', [
            'user_id' => $userId,
            'arc_code' => $arcCode,
            'response' => $response,
        ]);
        return $response['data'] ?? [];
    }


    public function getArcDetail(string $arcCode = ''): array
    {
        $appId     = config('business.bmo-service.app_id');
        $appSecret = config('business.bmo-service.app_secret');
        if (!$appId || !$appSecret) {
            throw new \RuntimeException('BMO服务配置错误');
        }

        if (empty($arcCode)) {
            throw new \RuntimeException('arc_code 空');
        }

        $response = Http::timeout(10)->asJson()->retry(2, 500) // 重试2次，间隔500毫秒
            ->withHeaders(
                [
                    'AppId'     => $appId,
                    'AppSecret' => $appSecret,
                ]
            )->get(
                $this->baseUri.'/innerapi/arc/detail',
                ['arc_code' => $arcCode]
            )->json();
        return $response['data'] ?? [];
    }

    /**
     * @param  string  $token
     * @param  int  $orgId
     *
     * @return array|mixed
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function getUserInfoByToken(string $token, int $orgId = 0): array
    {
        try {
            $orgId = $orgId ?: config('business.bmo-service.auth.org_id', 0);

            // 生成缓存键（使用token的MD5前12位避免过长）
            $cacheKey = 'user_info:'.substr(md5($token), 0, 12).':'.$orgId;

            // 尝试从缓存获取，缓存15分钟
            return Cache::remember(
                $cacheKey,
                15 * 60,
                function () use ($token, $orgId) {
                    $response = Http::timeout(10)->retry(2, 500) // 重试2次，间隔500毫秒
                        ->asJson()->withHeaders(['Authorization' => $token])
                        // ->throw()
                        ->get(
                            $this->baseUri.'api/apiUser/apiUserDetails',
                            ['org_id' => $orgId]
                        );

                    // 检查HTTP状态码
                    if (!$response->successful()) {
                        throw new \Exception(
                            'API请求失败，状态码: '.$response->status()
                        );
                    }

                    $data = $response->json();

                    // 验证响应结构
                    if (!isset($data['data']) || !is_array($data['data'])) {
                        throw new \Exception('API响应格式错误');
                    }

                    return $data['data'] ?? [];
                }
            );
        } catch (\Exception $e) {
            // 记录错误日志（隐藏敏感信息）
            Logger::API_SERVICE->error('获取用户信息失败', [
                'token_prefix' => substr($token, 0, 8).'...',
                'org_id'       => $orgId,
                'error'        => $e->getMessage(),
            ]);
            throw new \RuntimeException('获取用户信息失败: '.$e->getMessage());
        }
    }
}
