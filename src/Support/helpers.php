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
