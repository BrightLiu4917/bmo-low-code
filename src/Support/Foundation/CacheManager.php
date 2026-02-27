<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Support\Foundation;

use BrightLiu\LowCode\Traits\Context\WithContext;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class CacheManager
{
    use WithContext;

    /**
     * cache key
     *
     * @var string
     */
    protected string $key = '';

    /**
     * cache tag
     *
     * @var string
     */
    protected string $tag = '';

    /**
     * @param mixed $originalKey 原始key
     * @param string $prefix 前缀
     * @param bool $hasHash 对键名hash
     *
     * @return void
     */
    public function __construct(mixed $originalKey, string $prefix = '', bool $hasHash = true)
    {
        $this->key = $this->makeKey($originalKey, $prefix, $hasHash);

        $this->tag = $prefix;
    }

    /**
     * @param mixed $originalKey 原始key
     * @param string $prefix 前缀
     * @param bool $hasHash 对键名hash
     *
     * @return string
     */
    protected function makeKey(mixed $originalKey, string $prefix = '', bool $hasHash = true): string
    {
        if (empty($originalKey)) {
            return '';
        }

        $key = match (true) {
            is_numeric($originalKey) => (string) $originalKey,
            is_array($originalKey)   => json_encode(Arr::sort($originalKey)),
            is_object($originalKey)  => serialize($originalKey),
            default                  => $originalKey,
        };

        $key = $hasHash ? md5($key) : $key;

        return match (true) {
            empty($prefix) => sprintf('%s:%s', $this->getDiseaseCode(), $key),
            default        => sprintf('%s:%s:%s', $this->getDiseaseCode(), $prefix, $key)
        };
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return Repository
     */
    public function client(): Repository
    {
        return empty($this->tag) ? Cache::store() : Cache::tags($this->tag);
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     *
     * @param \DateTimeInterface|\DateInterval|int $ttl
     * @param \Closure $callback
     *
     * @return mixed
     */
    public function remember(\DateTimeInterface|\DateInterval|int $ttl, \Closure $callback): mixed
    {
        return $this->client()->remember($this->key, $ttl, $callback);
    }

    /**
     * Determine if an item exists in the cache.
     *
     * @return bool
     */
    public function has(): bool
    {
        return $this->client()->has($this->key);
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(mixed $default = null): mixed
    {
        return $this->client()->get($this->key, $default);
    }

    /**
     * Store an item in the cache.
     *
     * @param mixed $value
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     *
     * @return bool
     */
    public function put(mixed $value, \DateTimeInterface|\DateInterval|int|null $ttl = null): bool
    {
        return $this->client()->put($this->key, $value, $ttl);
    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @return bool
     */
    public function delete(): bool
    {
        return $this->client()->delete($this->key);
    }

    /**
     * Get a lock instance.
     *
     * @param int $seconds
     *
     * @return Lock
     */
    public function lock(int $seconds = 60): Lock
    {
        return Cache::lock($this->key, $seconds);
    }
}
