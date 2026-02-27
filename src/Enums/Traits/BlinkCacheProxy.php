<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Enums\Traits;

use BrightLiu\LowCode\Support\Foundation\BlinkCacheManager;

trait BlinkCacheProxy
{
    use CacheProxy;

    /**
     * @param mixed $originalKey 原始key
     * @param bool $hasHash 对键名hash
     */
    public function make(mixed $originalKey = '', bool $hasHash = true): BlinkCacheManager
    {
        if (empty(config('cache.stores.array'))) {
            config(['cache.stores.array' => ['driver' => 'array', 'serialize' => false]]);
        }

        return new BlinkCacheManager($originalKey, $this->value, $hasHash);
    }
}
