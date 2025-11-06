<?php

declare(strict_types=1);

if (!function_exists('class_map')) {
    /**
     * @throws \InvalidArgumentException
     */
    function class_map($class): string
    {
        $dependencies = config('low-code.dependencies', []);

        return $dependencies[$class] ?? $class;
    }
}


if (!function_exists('silence_event')) {
    /**
     * 静默触发事件
     *
     * @param  string|object  $event
     * @param  mixed  $payload
     * @param  bool  $halt
     * @return array|null
     */
    function silence_event(...$args)
    {
        return rescue(fn () => event(...$args));
    }
}