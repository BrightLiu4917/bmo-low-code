<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Enums\Traits;

use BrightLiu\LowCode\Support\Foundation\CacheManager;


trait CacheProxy
{
    /**
     * @param mixed $originalKey 原始key
     * @param bool $hasHash 对键名hash
     *
     * @return CacheManager
     */
    public function make(mixed $originalKey = '', bool $hasHash = true): CacheManager
    {
        return new CacheManager($originalKey, $this->value, $hasHash);
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     *
     * @param string $key
     * @param \DateTimeInterface|\DateInterval|int $ttl
     * @param \Closure $callback
     *
     * @return mixed
     */
    public function remember(string $key, \DateTimeInterface|\DateInterval|int $ttl, \Closure $callback): mixed
    {
        return $this->make($key)->remember($ttl, $callback);
    }

    /**
     * Determine if an item exists in the cache.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->make($key)->has();
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->make($key)->get($default);
    }

    /**
     * Store an item in the cache.
     *
     * @param string $key
     * @param mixed $value
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     *
     * @return bool
     */
    public function put(string $key, mixed $value, \DateTimeInterface|\DateInterval|int|null $ttl = null): bool
    {
        return $this->make($key)->put($value, $ttl);
    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key
     *
     * @return bool
     */
    public function delete(string $key): bool
    {
        return $this->make($key)->delete();
    }
}
