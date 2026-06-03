<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services;

/**
 * 患者列数据上下文中间层
 *
 * Controller 预取枚举映射和字段元信息到请求级静态缓存，
 * QueryResource / ColumnGroupResource 只读访问。
 * 避免 Resource 层直接调用 PatientColumnService 导致 N+1 API 请求。
 *
 * 使用场景：
 * - QueryResource: getDisplayedKeys() + getEnumMapping()
 * - ColumnGroupResource: getMeta() + getEnumOptions()
 */
class PatientColumnContext
{
    protected static ?array $context = null;
    protected static ?array $enumMapping = null;
    protected static ?array $fieldMeta = null;
    protected static ?array $displayedKeys = null;

    /**
     * Controller 调用：预取枚举映射 + 字段元信息 + 展示列 keys
     */
    public static function preload(array $columnKeys): void
    {
        static::$displayedKeys = $columnKeys;

        $context = PatientColumnService::instance()->buildConversionContext($columnKeys);

        // 按字段名映射
        $context['_enum_mapping'] = array_map(
            fn ($items) => array_column($items, null, 'enum_value'),
            array_column($context['_enum_mapping'] ?? [], 'enum_list', 'field_key')
        );

        // 按字段名映射
        $context['_field_meta'] = array_column($context['_field_meta'] ?? [], null, 'field_key');

        static::$context = $context;
        static::$enumMapping = $context['_enum_mapping'] ?? [];
        static::$fieldMeta = $context['_field_meta'] ?? [];
    }

    /**
     * 获取完整上下文数组（供 Conversion::withContext() 使用）
     */
    public static function getContext(): array
    {
        return static::$context ?? [];
    }

    /**
     * QueryResource 调用：获取预取的枚举映射（原始 key-value 格式）
     */
    public static function getEnumMapping(?string $key = null): array
    {
        if ($key === null) {
            return static::$enumMapping ?? [];
        }
        return static::$enumMapping[$key] ?? [];
    }

    public static function getEnumMappingValue(?string $key = null): array
    {
        return array_values(static::getEnumMapping($key) ?? []);
    }

    /**
     * QueryResource 调用：获取预取的展示列 keys
     */
    public static function getDisplayedKeys(): array
    {
        return static::$displayedKeys ?? [];
    }

    /**
     * ColumnGroupResource 调用：获取字段元信息
     */
    public static function getMeta(string $key): array
    {
        return static::$fieldMeta[$key] ?? [];
    }

    /**
     * ColumnGroupResource 调用：获取枚举数据（前端友好格式 [{value, label}]）
     */
    public static function getEnumOptions(string $key): array
    {
        $mapping = static::$enumMapping[$key] ?? null;

        if (!is_array($mapping)) {
            return [];
        }

        $enum = [];
        foreach ($mapping as $item) {
            $enum[] = ['value' => $item['enum_value'], 'label' => $item['enum_label']];
        }

        return $enum;
    }

    /**
     * 重置（测试用）
     */
    public static function reset(): void
    {
        static::$context = null;
        static::$enumMapping = null;
        static::$fieldMeta = null;
        static::$displayedKeys = null;
    }
}
