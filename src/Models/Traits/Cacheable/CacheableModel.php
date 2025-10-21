<?php

namespace BrightLiu\LowCode\Models\Traits\Cacheable;

use App\Observers\CacheableModelObserver;

trait CacheableModel
{
    public function getCacheKeyById(): string
    {
        return static::class . ":model:id:" . $this->getKey();
    }

    public function getCacheKeyByCode(): string
    {
        return static::class . ":model:code:" . $this->code;
    }

    public static function getTag(): string
    {
        return static::class;
    }

    public static function bootCacheableModel()
    {
        static::observe(CacheableModelObserver::class);
    }
}
