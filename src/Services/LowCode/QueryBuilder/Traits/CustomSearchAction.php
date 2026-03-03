<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\LowCode\QueryBuilder\Traits;

use Illuminate\Support\Arr;

trait CustomSearchAction
{
    private static string $customSearchActionPrefix = '_c.';

    private static string $customSearchActionDelimiter = '#@#';

    /**
     * 解析出自定义搜索动作，并从过滤条件中移除相关条件
     */
    protected function resolveCustomSearchActions(array $filters): array
    {
        $prefix = self::$customSearchActionPrefix;
        $customActions = [];
        $cleanedFilters = [];

        // 转换filters为统一的字符串格式便于检查
        $stringifiedFilters = array_map(
            fn ($item) => is_array($item) ? join(self::$customSearchActionDelimiter, Arr::flatten($item)) : $item,
            $filters
        );

        // 遍历原始filters，分离自定义搜索动作和普通条件
        foreach ($filters as $index => $item) {
            $stringItem = $stringifiedFilters[$index];

            // 检查是否为自定义搜索动作条件
            if (!empty($stringItem) && is_string($stringItem) && str_starts_with($stringItem, $prefix)) {
                // 解析自定义搜索动作
                // - 格式1: _c.search=xxxx
                preg_match('/^' . preg_quote($prefix, '/') . '(.*?)' . self::$customSearchActionDelimiter . '\=' . self::$customSearchActionDelimiter . '(.*?)$/', $stringItem, $matches);

                if (!empty($matches[1]) && !empty($matches[2])) {
                    $actionParams = null;

                    // - 格式2: _c.search.xxxx=123
                    if (str_contains($matches[1], '.')) {
                        $actionParams = $matches[2];
                        [0 => $matches[1], 1 => $matches[2]] = explode('.', $matches[1]);
                    }

                    $customActions[sprintf('%s:%s', $matches[1], $matches[2])] = $actionParams;
                }
            } else {
                // 保留非自定义搜索动作的条件
                $cleanedFilters[] = $item;
            }
        }

        return [
            'actions' => $customActions,
            'filters' => $cleanedFilters,
        ];
    }

    public function setCustomSearchActions(array $actions): void
    {
        $this->customSearchActions = $actions;
    }

    public function getCustomSearchActions(): array
    {
        return $this->customSearchActions;
    }

    public function hasCustomSearchAction(string|array $action): bool
    {
        $actions = array_keys($this->getCustomSearchActions());

        if (is_string($action)) {
            return in_array($action, $actions, true);
        }

        if (is_array($action)) {
            return count(array_intersect($action, $actions)) > 0;
        }

        return false;
    }

    public function getCustomSearchActionParams(string $action): mixed
    {
        $params = $this->getCustomSearchActions()[$action] ?? null;

        if (!empty($params) && is_string($params) && str_contains($params, self::$customSearchActionDelimiter)) {
            $params = explode(self::$customSearchActionDelimiter, $params);
        }

        return $params;
    }

    /**
     * 处理自定义搜索动作
     */
    protected function handleCustomSearchActions(array $searchActions, array $filters): void
    {
    }
}
