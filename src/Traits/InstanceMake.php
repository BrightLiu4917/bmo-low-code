<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Traits;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\App;

/**
 * 快速构建类的实例
 */
trait InstanceMake
{
    /**
     * make
     *
     * @param array $parameters
     *
     * @return static
     */
    public static function make(...$parameters): static
    {
        return new static(...$parameters);
    }

    /**
     * instance
     *
     * @param array ...$parameters
     *
     * @return static
     */
    public static function instance(...$parameters): static
    {
        App::singletonIf(static::class, function (Application $app) use ($parameters) {
            return new static(...$parameters);
        });

        return App::make(static::class);
    }
}
