<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Services;


use Illuminate\Support\Facades\Http;
use BrightLiu\LowCode\Tools\Signature;
use BrightLiu\LowCode\Enums\Foundation\Logger;

/**
 * @Class
 * @Description:
 * @created    : 2025-10-01 09:36:38
 * @modifier   : 2025-10-01 09:36:38
 */
class BmoAIApiService extends LowCodeBaseService
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
        $this->baseUri = config('business.bmo-service.ai.uri');
    }

    /**
     * @return string
     */
    public function getAccessToken(): string
    {
        try {
            // 先从缓存获取token
            $cacheKey    = 'bmo-ai-access-token';
            $cachedToken = cache()->get($cacheKey);
            if ($cachedToken) {
                return $cachedToken;
            }
            $appId     = config('business.bmo-service.ai.app_id');
            $appKey    = config('business.bmo-service.ai.app_key');
            $appSecret = config('business.bmo-service.ai.app_secret');
            $datetime  = now()->format('Y-m-d H:i:s');
            $nonce     = bin2hex(random_bytes(8));
            $signature = Signature::generateSignature(
                $appKey,
                $appSecret,
                $datetime,
                $nonce,
                []
            );
            $args      = [
                'app_id'    => $appId,
                'datetime'  => $datetime,
                'nonce'     => $nonce,
                'signature' => $signature,
            ];
            $response  = Http::asJson()->baseUrl($this->baseUri)->timeout(30)
                ->post('/api/v1/auth/app-token', $args);

            if ($response->successful()) {
                $responseData = $response->json();

                if ($responseData['code'] == 200) {
                    $token     = $responseData['data']['access_token'];
                    $expiresIn = $responseData['data']['expires_in'] ?? 3600;

                    // 缓存token，提前10分钟过期以避免边界问题
                    cache()->put(
                        $cacheKey,
                        $token,
                        ($expiresIn - 600)
                    );
                    return $token;
                }
            }

            return '';
        } catch (\Exception $throwable) {
            Logger::BMO_AI->error('获取-ai-access-token-异常', [
                'message' => $throwable->getMessage(),
                'args'    => $args ?? [],
                'file'    => $throwable->getFile(),
                'line'    => $throwable->getLine(),
                'trace'   => $throwable->getTraceAsString(),
            ]);
            return '';
        }
    }

    /**
     * @param  bool  $stream
     * @param  array  $sendMessage
     *
     * @return mixed
     */
    public function completionSend(
        string $content = '',
        bool $stream = false,
    ): array {
        $cacheKey = 'bmo-ai-completion-send-'.md5($content);
        $aiResult = cache()->get($cacheKey);
        if ($aiResult) {
            return $aiResult;
        }
        try {

            $resposeData = Http::asJson()->baseUrl($this->baseUri)->timeout(120)
                ->withHeaders(
                    ['Authorization' => 'Bearer '.$this->getAccessToken()]
                )->post(
                    'api/v1/completion/send',
                    [
                        'bot_id'   => config('business.bmo-service.ai.bot_id'),
                        'stream'   => $stream,
                        'messages' => [
                            [
                                'role'         => 'user',
                                'content_type' => 'text',
                                'content'      => $content,
                            ],
                        ],
                    ]
                );
            if ($resposeData->successful()) {
                $responseData = $resposeData->json();
                Logger::BMO_AI->info('获取-答案-成功', [
                    'response_data' => $responseData,
                    'content'       => $content,
                ]);

                if ($responseData['code'] == 200) {
                    $data = json_decode(
                        $responseData['data']['response']['content'],
                        true
                    );
                    cache()->put(
                        $cacheKey,
                        $data,
                        15
                    );
                    return $data;
                }
            }
        } catch (\Exception $throwable) {
            Logger::BMO_AI->error('获取-答案-异常', [
                'message'      => $throwable->getMessage(),
                'send_message' => $content ?? '',
                'file'         => $throwable->getFile(),
                'line'         => $throwable->getLine(),
                'trace'        => $throwable->getTraceAsString(),
            ]);
        }
        return [];
    }
}
