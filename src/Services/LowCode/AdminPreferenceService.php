<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\LowCode;

use BrightLiu\LowCode\Enums\Model\AdminPreference\SceneEnum;
use BrightLiu\LowCode\Models\AdminPreference;
use BrightLiu\LowCode\Services\PatientColumnContext;
use Gupo\BetterLaravel\Service\BaseService;

final class AdminPreferenceService extends BaseService
{
    /**
     * 处理 列表列配置，结合用户偏好设置.
     *
     * @param bool $enrich 是否附加字段元信息和枚举映射（默认 false）
     */
    public function handleColumnConfig(string $listCode, array $columnConfig, bool $enrich = false): array
    {
        try {
            $preference = AdminPreference::query()
                ->where('scene', SceneEnum::LIST_COLUMNS)
                ->where('pkey', $listCode)
                ->value('pvalue');

            // 获取预设配置
            $presetConfig = array_column($columnConfig, null, 'key');

            // 优先使用用户偏好设置，结合预设配置中的自定义选项
            if (!empty($preference)) {
                $columnConfig = array_map(
                    fn ($item) => array_merge([
                        'title' => $item['name'],
                        'key' => $item['column'],
                        'sortable' => $item['sortable'] ?? false,
                        'is_default_sort' => $item['is_default_sort'] ?? false,
                        'default_sort_order' => $item['default_sort_order'] ?? 'desc',
                    ], $presetConfig[$item['column']] ?? []),
                    $preference
                );
            }

            $columnConfig = array_map(
                fn ($item) => [
                    'title' => $item['title'],
                    'key' => $item['key'],
                    'sortable' => $item['sortable'] ?? false,
                    'is_default_sort' => $item['is_default_sort'] ?? false,
                    'default_sort_order' => $item['default_sort_order'] ?? 'desc',
                ],
                $columnConfig
            );
        } catch (\Throwable) {
        }

        // 列富化：附加 metadata + enum
        if ($enrich) {
            $columnConfig = $this->enrichColumnConfig($columnConfig);
        }

        return $columnConfig;
    }

    /**
     * 列富化 — 为每列附加字段元信息和枚举映射
     *
     * 从 PatientColumnContext 批量预取数据，逐列附加 metadata 和 enum。
     * 异常时降级返回原始列配置。
     */
    public function enrichColumnConfig(array $columnConfig): array
    {
        try {
            if (empty($columnConfig)) {
                return $columnConfig;
            }

            $columnKeys = array_column($columnConfig, 'key');
            PatientColumnContext::preload($columnKeys);

            return array_map(function (array $column): array {
                $key = $column['key'] ?? '';

                $metadata = PatientColumnContext::getMeta($key);

                return array_merge($column, [
                    'title' => !empty($metadata['field_short_name']) ? $metadata['field_short_name'] : $column['title'],
                    'metadata' => PatientColumnContext::getMeta($key),
                    'enum' => PatientColumnContext::getEnumMappingValue($key),
                ]);
            }, $columnConfig);
        } catch (\Throwable $e) {
            return $columnConfig;
        }
    }
}
