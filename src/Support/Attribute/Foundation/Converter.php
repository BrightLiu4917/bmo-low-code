<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Support\Attribute\Foundation;

use BrightLiu\LowCode\Support\Attribute\Concerns\WithApiEnumMapping;
use BrightLiu\LowCode\Support\Attribute\Contracts\Actions;
use BrightLiu\LowCode\Support\Attribute\Contracts\Convertable;
use Illuminate\Support\Str;

abstract class Converter implements Convertable, Actions
{
    use WithApiEnumMapping;

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
     * 是否参与获取 API 字段元信息
     *
     * 部分 Converter（如计算/组合字段）在 crowdkit 中不存在对应字段，
     * 重写此方法返回 false 以避免无效 API 请求。
     */
    public static function fetchFieldMeta(): bool
    {
        return true;
    }

    /**
     * 子类追加元数据
     *
     * 重写此方法返回额外元数据，与 API 元信息合并后返回。
     * 避免直接重写 metadata() 导致 API 元信息丢失。
     */
    protected function extraMetadata(): array
    {
        return [];
    }

    /**
     * 元信息
     */
    public function metadata(): array
    {
        $context = $this->getContext();
        $fieldKey = static::define();
        $meta = $context['_field_meta'][$fieldKey] ?? [];

        return array_merge($meta, $this->extraMetadata());
    }

    public function __toString()
    {
        return $this->value;
    }
}
