<?php

namespace BrightLiu\LowCode\Middleware;

use BrightLiu\LowCode\Enums\Foundation\Logger;
use BrightLiu\LowCode\Models\ApiAccessLog;
use BrightLiu\LowCode\Tools\Timer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 接口请求日志
 */
final class ApiAccessVia
{
    /**
     * 记录常规内容
     */
    public const RECORD_NORMAL = 1000;

    /**
     * 记录带参数的内容
     */
    public const RECORD_PARAMS = 2000;

    /**
     * 记录带参数的内容及响应内容
     */
    public const RECORD_ALL = 3000;

    /**
     * Handle
     *
     * @param Request $request
     * @param mixed $recordLevel 记录级别
     */
    public function handle($request, \Closure $next, $recordLevel = '')
    {
        if (!config('low-code.api_access_log.enabled', false)) {
            return $next($request);
        }

        $log = $timer = null;

        try {
            /** @var Timer */
            $timer = tap(new Timer(), fn ($timer) => $timer->start());

            // 记录请求参数
            $requestParams = null;
            switch ($recordLevel) {
                case self::RECORD_ALL:
                case self::RECORD_PARAMS:
                    $requestParams = [
                        'headers' => $request->headers->all(),
                        'query' => $request->query(),
                        'body' => $request->post(),
                        'raw' => $request->getContent(),
                    ];

                    break;
            }

            // TODO: 写法待完善
            $driver = config('low-code.api_access_log.driver', 'database');
            if ('database' == $driver) {
                $log = ApiAccessLog::query()->create([
                    'request_params' => $requestParams,
                    'request_ip' => $request->ip(),
                    'request_route' => $request->getPathInfo(),
                    'created_at' => now(),
                ]);
            } else {
                Logger::API_ACCESS_LOG->info($request->getPathInfo(), [
                    'request_ip' => $request->ip(),
                    'request_route' => $request->getPathInfo(),
                    'request_params' => $requestParams,
                ]);
            }
        } catch (\Throwable $e) {
            Logger::LARAVEL->error('ApiAccessVia error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return tap(
            $next($request),
            function ($response) use ($log, $timer, $recordLevel) {
                try {
                    if (empty($log)) {
                        return;
                    }

                    // 记录响应内容
                    $responsData = null;
                    if ($response->isSuccessful()) {
                        switch ($recordLevel) {
                            case self::RECORD_ALL:
                                $responsData = $response instanceof JsonResponse ? $response->getData() : $response->getContent();
                                break;
                        }
                    }

                    $processTime = (int) $timer->stop();
                    $processTimeFormat = sprintf('%s s', $processTime / 1000);

                    $log->update([
                        'process_time' => $processTime,
                        'process_time_format' => $processTimeFormat,
                        'process_exception' => rescue(
                            fn () => $response?->exception?->getMessage() ?: ($response?->getData()?->message ?? ''),
                            '',
                            false
                        ),
                        'response_data' => $responsData,
                    ]);
                } catch (\Throwable $e) {
                    Logger::LARAVEL->error('ApiAccessVia error', [
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        );
    }

    public static function withNormal(): string
    {
        return self::class . ':' . self::RECORD_NORMAL;
    }

    public static function withParams(): string
    {
        return self::class . ':' . self::RECORD_PARAMS;
    }

    public static function withAll(): string
    {
        return self::class . ':' . self::RECORD_ALL;
    }
}
