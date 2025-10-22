<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Support\Attribute\Foundation;

use BrightLiu\LowCode\Support\Attribute\Contracts\Actions;
use BrightLiu\LowCode\Support\Attribute\Contracts\Convertable;
use Illuminate\Support\Str;

abstract class Converter implements Convertable, Actions
{
    protected mixed $original = null;

    public function __construct(
        protected mixed $value,
        protected array $attributes = [],
        protected array $context = []
    ) {
        $this->original = $value;
    }

    public static function define(): string
    {
        return Str::snake(class_basename(static::class));
    }

    public function getter(): mixed
    {
        return $this->value;
    }

    public function setter(mixed $value): void
    {
        $this->value = $value;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttributeValue(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    // ==================== Actions ====================

    /**
     * 值
     */
    public function value(): mixed
    {
        return $this->value;
    }

    /**
     * 变体
     */
    public function variant(): mixed
    {
        return $this->value();
    }

    /**
     * 单位
     */
    public function unit(): string
    {
        return '';
    }

    /**
     * 信息描述
     */
    public function information(): string
    {
        return '';
    }

    /**
     * 是否只读
     */
    public function readonly(): bool
    {
        return false;
    }

    /**
     * 元信息
     */
    public function metadata(): array
    {
        return [];
    }

    public function __toString()
    {
        return $this->value;
    }
}
