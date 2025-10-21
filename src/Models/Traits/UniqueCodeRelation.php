<?php

declare(strict_types=1);

namespace  BrightLiu\LowCode\Models\Traits;

use BrightLiu\LowCode\Tools\Uuid;

trait UniqueCodeRelation
{
    /**
     * @return void
     */
    protected static function bootUniqueCodeRelation()
    {
        static::creating(function ($model) {
            if (empty($model->code)) {
                $model->code = Uuid::generate();
            }
        });
    }
}
