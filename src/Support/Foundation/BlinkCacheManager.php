<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Support\Foundation;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

class BlinkCacheManager extends CacheManager
{
    /**
     * @return Repository
     */
    public function client(): Repository
    {
        return Cache::store('array');
    }

    /**
     * @return void
     */
    public static function flush(): void
    {
        /** @phpstan-ignore-next-line */
        Cache::store('array')->flush();
    }
}
