<?php

declare(strict_types=1);

namespace  BrightLiu\LowCode\Core;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Exceptions\ServiceException;
use BrightLiu\LowCode\Services\LowCode\DatabaseSourceService;
use Illuminate\Database\Connection;
use PDO;

/**
 * 动态数据库连接管理器
 */
final class DbConnectionManager
{
    public const CONFIG_CACHE_KEY_PREFIX = 'db-config:';

    private static ?self $instance = null;

    /**
     * @var array
     */
    public array $connections = [];

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct() {}
    private function __clone() {}

    public function defaultConfig(): array
    {
        return [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => [
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_PERSISTENT => true,
            ],
        ];
    }

    /**
     * 获取数据库连接
     */
    public function getConnection(string $code = '', array $dbConfig = []): Connection
    {
        if (empty($code) && empty($dbConfig)) {
            throw new ServiceException('动态获取数据库连接失败，请检查参数');
        }

        // 优先使用传入配置，不缓存
        if (!empty($dbConfig)) {
            return $this->createConnection($code, $dbConfig);
        }

        if (isset($this->connections[$code])) {
            return $this->connections[$code];
        }

        $cacheKey = self::CONFIG_CACHE_KEY_PREFIX . $code;

        try {
            $dbConfig = Cache::remember($cacheKey, now()->addMinutes(60), function () use ($code) {
                $source = DatabaseSourceService::instance()->fetchDataByCode($code);
                if (empty($source)) {
                    throw new ServiceException("数据源 '{$code}' 不存在");
                }
                return array_merge($source->toArray(), $this->defaultConfig());
            });

            return $this->connections[$code] = $this->createConnection($code, $dbConfig);

        } catch (ServiceException $e) {
            throw $e;
        } catch (\Throwable $exception) {
            Log::error("低代码动态链接数据库连接失败 [{$code}]：" . $exception->getMessage(), [
                'code' => $code,
                'exception' => $exception,
            ]);
            throw new ServiceException("动态数据库连接失败，请稍后再试");
        }
    }

    /**
     * 创建连接（含 config 注册）
     */
    protected function createConnection(string $code, array $dbConfig): Connection
    {
        if (!Config::has("database.connections.$code")) {
            Config::set("database.connections.$code", $dbConfig);
        }

        // Laravel 会自动复用连接，DB::connection() 是懒加载的
        return DB::connection($code);
    }

    /**
     * 清除内存 + DB 管理器中的连接
     */
    public function flushConnection(string $code): void
    {
        unset($this->connections[$code]);
        DB::purge($code);
    }

    /**
     * 清除 Redis 缓存的配置
     */
    public function flushConfigCache(string $code): void
    {
        Cache::forget(self::CONFIG_CACHE_KEY_PREFIX . $code);
    }

    /**
     * 包装执行方法带重试
     */
    public function withRetry(callable $callback, int $retries = 3, int $delay = 100)
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < $retries) {
            try {
                return $callback();
            } catch (\Throwable $e) {
                $lastException = $e;
                $attempts++;
                if ($attempts < $retries) {
                    usleep($delay * 1000);
                }
            }
        }

        throw $lastException ?? new ServiceException('连接失败');
    }
}
