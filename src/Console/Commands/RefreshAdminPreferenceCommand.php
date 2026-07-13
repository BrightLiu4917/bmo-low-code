<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Console\Commands;

use BrightLiu\LowCode\Enums\Model\AdminPreference\SceneEnum;
use BrightLiu\LowCode\Models\AdminPreference;
use BrightLiu\LowCode\Services\CrowdKitService;
use BrightLiu\LowCode\Context\OrgContext;
use BrightLiu\LowCode\Context\DiseaseContext;
use BrightLiu\LowCode\Context\AuthContext;
use Illuminate\Console\Command;

/**
 * 刷新 AdminPreference 表中的 pvalue 字段数据
 * 完善其数据结构，使其与 handleColumnConfig 一致
 */
final class RefreshAdminPreferenceCommand extends Command
{
    protected $signature = 'low-code:refresh-admin-preference
        {--list-code= : 特定列表代码}
        {--dry-run : 仅显示要更新的数据，不实际更新}
        {--system-code= : 系统编码}
        {--org-id= : 机构ID}
        {--token= : API访问令牌}
        {--request-source= : 请求来源}
        {--arc-code= : 地区编码}
        {--scene-code= : 场景编码}';

    protected $description = '刷新管理员偏好设置中列的数据结构';

    /**
     * 执行命令
     */
    public function handle(): int
    {
        try {
            $isDryRun = (bool) $this->option('dry-run');
            $listCode = (string) $this->option('list-code');

            // 初始化全局上下文
            $this->initGlobalContext();

            // 获取需要处理的 AdminPreference 记录
            $query = AdminPreference::query()->where('scene', SceneEnum::LIST_COLUMNS);

            if (!empty($listCode)) {
                $query->where('pkey', $listCode);
            }

            if (!empty($sceneCode = (string) $this->option('scene-code'))) {
                $query->where('scene_code', $sceneCode);
            }

            $preferences = $query->get();

            if ($preferences->isEmpty()) {
                $this->info('没有找到需要处理的行');
                return 0;
            }

            $this->info("找到 {$preferences->count()} 条记录");

            $updatedCount = 0;

            foreach ($preferences as $preference) {
                // 为每个偏好设置初始化上下文
                $this->initContextForPreference($preference);

                $oldPvalue = $preference->pvalue ?? [];

                // 完善 pvalue 数据结构
                $newPvalue = $this->enhancePvalue($oldPvalue);

                // 比较是否有变化
                if ($oldPvalue !== $newPvalue) {
                    $updatedCount++;

                    if ($isDryRun) {
                        $this->line("");
                        $this->warn("列表代码: {$preference->pkey}");
                        $this->line("原始数据:");
                        $this->line(json_encode($oldPvalue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                        $this->line("完善后数据:");
                        $this->line(json_encode($newPvalue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    } else {
                        $preference->update(['pvalue' => $newPvalue]);
                        $this->info("已更新: {$preference->pkey}");
                    }
                }
            }

            if ($isDryRun) {
                $this->line("");
                $this->info("干运行模式: 共发现 {$updatedCount} 条需要更新的记录");
            } else {
                $this->info("成功更新 {$updatedCount} 条记录");
            }

            return 0;
        } catch (\Throwable $e) {
            $this->error("错误: {$e->getMessage()}");
            if ($this->isVerbose()) {
                $this->error($e->getTraceAsString());
            }
            return 1;
        }
    }

    /**
     * 初始化全局上下文
     */
    protected function initGlobalContext(): void
    {
        $systemCode = (string) $this->option('system-code');
        $orgId = (int) $this->option('org-id');
        $token = (string) $this->option('token');
        $requestSource = (string) $this->option('request-source');
        $arcCode = (string) $this->option('arc-code');

        // 只有在提供了必要的参数时才初始化认证上下文
        if (!empty($systemCode) || !empty($token) || !empty($requestSource)) {
            AuthContext::init($systemCode, $orgId, $token, $requestSource, $arcCode);
        }
    }

    /**
     * 为指定的 AdminPreference 初始化上下文
     */
    protected function initContextForPreference(AdminPreference $preference): void
    {
        $sceneCode = (string) $this->option('scene-code');

        // 初始化病种上下文
        DiseaseContext::init($preference->disease_code, $sceneCode);

        // 初始化机构上下文（使用基本的 org_code）
        OrgContext::init($preference->org_code);
    }

    /**
     * 获取当前上下文下的可排序字段映射
     */
    protected function getSortableColumnsMap(): array
    {
        try {
            $crowdKitService = CrowdKitService::instance();
            $optionalColumns = $crowdKitService->getOptionalColumns();

            $sortableColumnsMap = [];

            // 遍历所有列分组，提取字段信息
            foreach ($optionalColumns as $group) {
                foreach ($group['columns'] ?? [] as $column) {
                    $columnName = $column['column'] ?? null;

                    if (!empty($columnName)) {
                        $sortableColumnsMap[$columnName] = [
                            'name' => $column['name'] ?? '',
                            'type' => $column['type'] ?? '',
                        ];
                    }
                }
            }

            return $sortableColumnsMap;
        } catch (\Throwable $e) {
            $this->warn("加载 optionalColumns 失败: {$e->getMessage()}");
            // 失败时返回空数组
            return [];
        }
    }

    /**
     * 完善 pvalue 数据结构
     * 将其完善为和 handleColumnConfig 一样的结构
     */
    protected function enhancePvalue(array $pvalue): array
    {
        if (empty($pvalue)) {
            return [];
        }

        // 获取当前上下文下的可排序字段映射
        $sortableColumnsMap = $this->getSortableColumnsMap();

        return array_map(
            function ($item) use ($sortableColumnsMap) {
                // 如果已经是完整的结构，则验证并返回
                if ($this->isCompleteStructure($item)) {
                    return $this->normalizeItem($item);
                }

                $columnName = $item['column'] ?? $item;

                // 从 sortableColumnsMap 获取字段信息
                $columnInfo = $sortableColumnsMap[$columnName] ?? null;

                if ($columnInfo === null) {
                    // 如果找不到字段信息，使用基础结构
                    return $this->createBaseStructure($columnName, $item);
                }

                // 判断是否可排序：日期相关类型
                $isSortable = in_array(
                    $columnInfo['type'] ?? '',
                    explode_str_array(config('low-code.sortable-columns-types', 'date,time,datetime')),
                    true
                );

                return [
                    'name' => $columnInfo['name'] ?? '',
                    'column' => $columnName,
                    'sortable' => $isSortable,
                    'is_default_sort' => $item['is_default_sort'] ?? false,
                    'default_sort_order' => $item['default_sort_order'] ?? 'desc',
                ];
            },
            $pvalue
        );
    }

    /**
     * 判断是否已是完整的数据结构
     */
    protected function isCompleteStructure($item): bool
    {
        if (!is_array($item)) {
            return false;
        }

        // 完整结构需要包含这些必要字段
        return isset($item['name']) &&
               isset($item['column']) &&
               isset($item['sortable']) &&
               isset($item['is_default_sort']) &&
               isset($item['default_sort_order']);
    }

    /**
     * 标准化已完整的 item，确保类型正确
     */
    protected function normalizeItem(array $item): array
    {
        return [
            'name' => (string) ($item['name'] ?? ''),
            'column' => (string) ($item['column'] ?? ''),
            'sortable' => (bool) ($item['sortable'] ?? false),
            'is_default_sort' => (bool) ($item['is_default_sort'] ?? false),
            'default_sort_order' => (string) ($item['default_sort_order'] ?? 'desc'),
        ];
    }

    /**
     * 创建基础完整结构
     * 从已有 item 字段中提取信息
     */
    protected function createBaseStructure($columnName, $item): array
    {
        return [
            'name' => $item['name'] ?? $item['title'] ?? '',
            'column' => $columnName,
            'sortable' => $item['sortable'] ?? false,
            'is_default_sort' => $item['is_default_sort'] ?? false,
            'default_sort_order' => $item['default_sort_order'] ?? 'desc',
        ];
    }
}
