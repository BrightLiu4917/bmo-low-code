<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\LowCode\QueryBuilder;

use BrightLiu\LowCode\Enums\Foundation\BlinkCacheable;
use BrightLiu\LowCode\Services\Contracts\ILowCodeQueryBuilder;
use BrightLiu\LowCode\Services\QueryEngineService;
use Illuminate\Support\Arr;

/**
 * 针对mysql优化查询逻辑
 *
 * PS： DefaultQueryBuilder适用与TiDB
 */
class MysqlQueryBuilder extends DefaultQueryBuilder implements ILowCodeQueryBuilder
{
    /**
     * 是否需要关联场景表
     */
    protected bool $hasBizScene = true;

    /**
     * 是否关联人群分类表
     */
    protected bool $hasCrowdType = true;

    /**
     * 是否关联宽表
     */
    protected bool $hasWidthTable = true;

    /**
     * 判断是否存在场景表相关条件
     */
    protected function checkHasBizSceneFilter(array $filters): bool
    {
        foreach (Arr::dot($filters) as $key => $value) {
            if (
                str_contains((string) $value, 't2.')
                || str_contains((string) $value, '`t2`.')
                || str_contains((string) $key, 't2.')
                || str_contains((string) $key, '`t2`.')
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * 判断是否有人群分类表相关条件
     */
    protected function checkHasCrowdTypeFilter(array $filters): bool
    {
        $groupIds = [];

        // 提取条件中的group_id条件值
        // TODO: 目前仅支持简单的条件提取，复杂条件（如嵌套、OR等）可能无法正确提取，待完善
        foreach ($filters as $item) {
            if (str_contains((string) $item[0], 't3.group_id') && '=' == $item[1]) {
                $value = (string) $item[2] ?? '';
                if (is_array($value)) {
                    $groupIds = array_merge($groupIds, $value);
                } else {
                    $groupIds[] = $value;
                }
            }
        }

        // 获取人群分类表中的分类类型，判断是否需要关联人群分类表
        if (!empty($groupIds = array_values(array_unique(array_filter($groupIds))))) {
            $groupTypes = [];
            if (!empty($crowdGroupTable = config('low-code.bmo-baseline.database.crowd-group-table', 'user_group'))) {
                $groupTypes = BlinkCacheable::BMP_CROWD_GROUP->remember(
                    key: (string) md5(join(',', $groupIds)),
                    ttl: 60 * 60,
                    callback: fn () => QueryEngineService::instance()
                        ->autoClient()
                        ->useTable($crowdGroupTable)
                        ->getQueryBuilder()
                        ->whereIn('id', $groupIds)
                        ->pluck('select_type')
                        ->toArray()
                );
            }

            // 如果存在非“9”，则需要关联人群分类表
            return !empty(array_diff($groupTypes, ['9']));
        }

        // 兜底处理，兼容深度的条件
        foreach (Arr::dot($filters) as $key => $value) {
            if (
                str_contains((string) $value, 't3.group_id')
                || str_contains((string) $value, 't3.')
                || str_contains((string) $value, '`t3`.')
                || str_contains((string) $key, 't3.')
                || str_contains((string) $key, '`t3`.')
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * 判断是否有宽表相关条件
     */
    protected function checkHasWidthTableFilter(array $filters): bool
    {
        foreach (Arr::dot($filters) as $key => $value) {
            if (
                str_contains((string) $value, 't1.')
                || str_contains((string) $value, '`t1`.')
                || str_contains((string) $key, 't1.')
                || str_contains((string) $key, '`t1`.')
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * 移除group_id条件
     */
    protected function removeGroupIdFilter(array $filters): array
    {
        $newFilters = [];

        foreach ($filters as $item) {
            if (str_contains((string) $item[0], 't3.group_id')) {
                continue;
            }
            $newFilters[] = $item;
        }

        return $newFilters;
    }

    /**
     * 构建基本的关联查询
     */
    public function relationQueryEngine(array $filters): array
    {
        // 宽表 表名
        $widthTable = config('low-code.bmo-baseline.database.crowd-psn-wdth-table', '');

        // 人群分类表 表名
        $crowdTypeTable = config('low-code.bmo-baseline.database.crowd-type-table', '');

        // 场景表 表名
        $bizSceneTable = $this->bizSceneTable;

        // 是否需要关联场景表
        $hasBizScene = true;

        // 是否关联人群分类表
        $hasCrowdType = true;

        // 是否关联宽表
        $hasWidthTable = true;

        if ($this->isQueryCount) {
            $hasBizScene = $this->checkHasBizSceneFilter($filters);

            $hasWidthTable = $this->checkHasWidthTableFilter($filters);
        }

        // 人群分类表的关联比较特殊，一般情况下人群分类的select_type=9时，可以不需要关联
        $hasCrowdType = $this->checkHasCrowdTypeFilter($filters);

        // TODO: 写法待完善
        if ($hasBizScene && $hasCrowdType && $hasWidthTable) {
            $this->queryEngine->useTable($crowdTypeTable . ' as t3')
                ->innerJoin($widthTable . ' as t1', 't3.empi', '=', 't1.empi')
                ->innerJoin($bizSceneTable . ' as t2', 't1.empi', '=', 't2.empi')
                ->select(['t2.empi']);
        } elseif ($hasBizScene && $hasCrowdType) {
            $this->queryEngine->useTable($bizSceneTable . ' as t2')
                ->innerJoin($crowdTypeTable . ' as t3', 't2.empi', '=', 't3.empi')
                ->select(['t2.empi']);
        } elseif ($hasBizScene && $hasWidthTable) {
            $this->queryEngine->useTable($bizSceneTable . ' as t2')
                ->innerJoin($widthTable . ' as t1', 't2.empi', '=', 't1.empi')
                ->select(['t2.empi']);
        } elseif ($hasCrowdType && $hasWidthTable) {
            $this->queryEngine->useTable($widthTable . ' as t1')
                ->innerJoin($crowdTypeTable . ' as t3', 't1.empi', '=', 't3.empi')
                ->select(['t1.empi']);
        } elseif ($hasCrowdType) {
            $this->queryEngine->useTable($crowdTypeTable . ' as t3')
                ->select(['t3.empi']);
        } elseif ($hasBizScene) {
            $this->queryEngine->useTable($bizSceneTable . ' as t2')
                ->select(['t2.empi']);
        } elseif ($hasWidthTable) {
            $this->queryEngine->useTable($widthTable . ' as t1')
                ->select(['t1.empi']);
        }

        // 不需要关联人群分类表时，移除相关条件
        if (!$hasCrowdType) {
            $filters = $this->removeGroupIdFilter($filters);
        }

        $this->hasBizScene = $hasBizScene;
        $this->hasCrowdType = $hasCrowdType;
        $this->hasWidthTable = $hasWidthTable;

        return $filters;
    }

    /**
     * 应用过滤条件
     */
    public function applyFilters(array $filters): void
    {
        $this->queryEngine->whereMixed($filters);
    }

    /**
     * 应用排序规则
     */
    public function applyOrderBy(): void
    {
        // 输入的排序规则
        $inputOrderBy = $this->queryParams['order_by'] ?? [];

        // 预设的排序规则
        $defaultOrderBy = $this->config['default_order_by_json'] ?? [];

        $orderBys = array_merge($inputOrderBy, $defaultOrderBy);

        // 根据连表情况调整排序字段前缀
        $recommendTbEmpi = match (true) {
            $this->hasBizScene => 't2.empi',
            $this->hasWidthTable => 't1.empi',
            $this->hasCrowdType => 't3.empi',
            default => ''
        };
        if (!empty($recommendTbEmpi)) {
            $orderBys = array_map(
                function ($item) use ($recommendTbEmpi) {
                    if (!empty($item[0]) && preg_match('/t\d\.empi/', (string) $item[0])) {
                        $item[0] = $recommendTbEmpi;
                    }

                    return $item;
                },
                $orderBys
            );
        }

        $this->queryEngine->multiOrderBy($orderBys);
    }
}
