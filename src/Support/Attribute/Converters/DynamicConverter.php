<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Support\Attribute\Converters;

use BrightLiu\LowCode\Support\Attribute\Foundation\Converter;

/**
 * 非枚举字段统一回退 Converter
 *
 * 对于没有定义 Converter 的字段（如纯展示列名、计算字段等），
 * 提供统一的回退处理，从 API context 中读取 variant 和 metadata。
 *
 * 不注册到 via using() 的 converter 集合中，由 Conversion::resolveConverter()
 * 在 fallback 模式下自动实例化。
 */
class DynamicConverter extends Converter
{
    protected string $fieldKey;

    public function __construct(
        string $fieldKey,
        mixed $value = null,
        array $attributes = [],
        array $context = []
    ) {
        $this->fieldKey = $fieldKey;
        parent::__construct($value, $attributes, $context);
    }

    /**
     * define() 返回空，仅用于占位
     * DynamicConverter 不注册到 via using() 的 converter 集合中
     */
    public static function define(): string
    {
        return '';
    }

    /**
     * 变体值：优先从 API 枚举映射获取，无则返回原始值
     */
    public function variant(): mixed
    {
        $context = $this->getContext();
        $enumMapping = $context['_enum_mapping'][$this->fieldKey] ?? null;

        if (is_array($enumMapping) && array_key_exists((string) $this->value, $enumMapping)) {
            return $enumMapping[(string) $this->value]['enum_label'];
        }

        return $this->value;
    }

    /**
     * 元信息：从 API field_meta 获取
     */
    public function metadata(): array
    {
        $context = $this->getContext();

        return $context['_field_meta'][$this->fieldKey] ?? [];
    }
}
