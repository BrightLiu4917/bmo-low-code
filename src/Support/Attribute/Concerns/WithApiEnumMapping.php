<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Support\Attribute\Concerns;

/**
 * API 枚举映射转换 Trait
 *
 * 引入到 Converter 基类，所有子类自动继承。
 * 优先从上下文查找枚举映射，降级到硬编码映射。
 *
 * @mixin \BrightLiu\LowCode\Support\Attribute\Foundation\Converter
 */
trait WithApiEnumMapping
{
    /**
     * 解析枚举变体值
     *
     * @param mixed $value            原始值
     * @param array $hardcodedMapping 硬编码映射（降级用）
     * @param mixed $default          默认值
     * @return mixed
     */
    protected function resolveEnumVariant(mixed $value, array $hardcodedMapping = [], mixed $default = ''): mixed
    {
        $context = $this->getContext();
        $fieldKey = static::define();

        // 1. 优先从上下文查找
        $enumMapping = $context['_enum_mapping'][$fieldKey] ?? null;
        if (is_array($enumMapping) && array_key_exists((string) $value, $enumMapping)) {
            return $enumMapping[(string) $value]['enum_label'];
        }

        // 2. 降级到硬编码映射
        if (array_key_exists($value, $hardcodedMapping)) {
            return $hardcodedMapping[$value];
        }

        return $default;
    }
}
