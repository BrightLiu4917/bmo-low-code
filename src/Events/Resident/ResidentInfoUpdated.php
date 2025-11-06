<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Events\Resident;

/**
 * 居民: 信息更新后
 */
class ResidentInfoUpdated
{
    public function __construct(
        public readonly string $empi,
        public readonly array $attributes
    ) {
    }

    public function getEmpi(): string
    {
        return $this->empi;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function get(string $key, $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * 判断所有key是否发生变化
     */
    public function has(string|array $key): bool
    {
        return empty(array_diff((array) $key, array_keys($this->attributes)));
    }

    /**
     * 判断任意key是否发生变化
     */
    public function any(string|array $key): bool
    {
        return !empty(array_intersect((array) $key, array_keys($this->attributes)));
    }
}
