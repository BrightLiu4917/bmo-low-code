<?php

namespace BrightLiu\LowCode\Models\Traits\Cacheable;

use BrightLiu\LowCode\Services\CacheModel\CustomBuilderService;

trait NewEloquentBuilder
{
    public function newEloquentBuilder($query)
    {
        return new CustomBuilderService($query);
    }
}
