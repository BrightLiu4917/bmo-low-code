<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\LowCode\QueryBuilder;

use BrightLiu\LowCode\Context\AdminContext;
use BrightLiu\LowCode\Enums\Foundation\BlinkCacheable;
use BrightLiu\LowCode\Services\Contracts\ILowCodeQueryBuilder;
use BrightLiu\LowCode\Services\QueryEngineService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

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
        if ($this->hasCustomSearchAction(['search:managed_patients', 'search:assigned_patients', 'search:follow_patients'])) {
            return true;
        }

        if (
            in_array(
                config('low-code.custom-query.builder', ''),
                [
                    ExcludeExitedMysqlQueryBuilder::class,
                    ExcludeExitedQueryBuilder::class,
                    'exclude_exited',
                    'exclude_exited_mysql',
                ]
            )
        ) {
            return true;
        }

        if (!$this->isQueryCount) {
            return $this->hasBizScene;
        }

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
        // 开启数据权限时强制需要
        if (config('low-code.data-permission-enabled', true)) {
            return true;
        }

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
        $this->hasBizScene = $hasBizScene = $this->checkHasBizSceneFilter($filters);

        // 是否关联宽表
        $this->hasWidthTable = $hasWidthTable = $this->checkHasWidthTableFilter($filters);

        // 是否关联人群分类表
        // 人群分类表的关联比较特殊，一般情况下人群分类的select_type=9时，可以不需要关联
        $this->hasCrowdType = $hasCrowdType = $this->checkHasCrowdTypeFilter($filters);

        // TODO: 写法待完善
        if ($hasBizScene && $hasCrowdType && $hasWidthTable) {
            $this->queryEngine->useTable($this->recommendIndex($crowdTypeTable, 't3'))
                ->innerJoin($this->recommendIndex($widthTable, 't1'), 't3.empi', '=', 't1.empi')
                ->innerJoin($this->recommendIndex($bizSceneTable, 't2'), 't1.empi', '=', 't2.empi')
                ->select([$this->recommendSortEmpi('t2.empi')]);
        } elseif ($hasBizScene && $hasCrowdType) {
            $this->queryEngine->useTable($this->recommendIndex($bizSceneTable, 't2'))
                ->innerJoin($this->recommendIndex($crowdTypeTable, 't3'), 't2.empi', '=', 't3.empi')
                ->select([$this->recommendSortEmpi('t2.empi')]);
        } elseif ($hasBizScene && $hasWidthTable) {
            $this->queryEngine->useTable($this->recommendIndex($bizSceneTable, 't2'))
                ->innerJoin($this->recommendIndex($widthTable, 't1'), 't2.empi', '=', 't1.empi')
                ->select([$this->recommendSortEmpi('t2.empi')]);
        } elseif ($hasCrowdType && $hasWidthTable) {
            $this->queryEngine->useTable($this->recommendIndex($widthTable, 't1'))
                ->innerJoin($this->recommendIndex($crowdTypeTable, 't3'), 't1.empi', '=', 't3.empi')
                ->select([$this->recommendSortEmpi('t2.empi')]);
        } elseif ($hasCrowdType) {
            $this->queryEngine->useTable($crowdTypeTable . ' as t3')
                ->select([$this->recommendSortEmpi('t2.empi')]);
        } elseif ($hasBizScene) {
            $this->queryEngine->useTable($bizSceneTable . ' as t2')
                ->select([$this->recommendSortEmpi('t2.empi')]);
        } elseif ($hasWidthTable) {
            $this->queryEngine->useTable($widthTable . ' as t1')
                ->select([$this->recommendSortEmpi('t2.empi')]);
        } else {
            $this->queryEngine->useTable($bizSceneTable . ' as t2')
                ->select([$this->recommendSortEmpi('t2.empi')]);
        }

        // 不需要关联人群分类表时，移除相关条件
        if (!$hasCrowdType) {
            $filters = $this->removeGroupIdFilter($filters);
        }

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

        // 为了有效的利用索引，根据连表情况调整排序字段前缀
        $recommendEmpi = $this->recommendSortEmpi();

        if (!empty($recommendEmpi)) {
            $orderBys = array_map(
                function ($item) use ($recommendEmpi) {
                    if (!empty($item[0]) && preg_match('/t\d\.empi/', (string) $item[0])) {
                        $item[0] = $recommendEmpi;
                    }

                    return $item;
                },
                $orderBys
            );
        }

        $this->queryEngine->multiOrderBy($orderBys);
    }

    protected function recommendSortEmpi(string $default = ''): string
    {
        // 针对索引优化
        // TODO: 写法待完善
        $searchKey = join(Arr::flatten($this->filters));
        if ($this->hasWidthTable && str_contains($searchKey, 't1.rsdnt_nm')) {
            return 't1.empi';
        }
        if ($this->hasBizScene && str_contains($searchKey, 't2.manage_status')) {
            return 't2.empi';
        }
        if ($this->hasWidthTable && str_contains($searchKey, 't2.assign_manage_doctor_code')) {
            return 't2.empi';
        }

        return match (true) {
            $this->hasBizScene => 't2.empi',
            $this->hasWidthTable => 't1.empi',
            $this->hasCrowdType => 't3.empi',
            default => $default
        };
    }

    protected function recommendJoinEmpi(string $default = ''): string
    {
        return match (true) {
            $this->hasCrowdType => 't3.empi',
            $this->hasBizScene => 't2.empi',
            $this->hasWidthTable => 't1.empi',
            default => $default
        };
    }

    protected function recommendIndex(string $table, string $alias = 't1'): mixed
    {
        $indexPlaceholder = '';

        if (config('low-code.custom-query.options.force_index', false)) {
            $searchKey = join(Arr::flatten($this->filters));

            // 针对索引优化
            // TODO: 写法待完善
            if ($table == config('low-code.bmo-baseline.database.crowd-psn-wdth-table', '')) {
                if ($this->hasWidthTable && str_contains($searchKey, 't2.assign_manage_doctor_code')) {
                    $indexPlaceholder = 'idx_empi_assign';
                }
            }
        }

        return match (true) {
            !empty($indexPlaceholder) => DB::raw(sprintf('%s as %s force index(%s)', $table, $alias, $indexPlaceholder)),
            default => sprintf('%s as %s', $table, $alias),
        };
    }

    /**
     * 处理自定义搜索动作
     */
    protected function handleCustomSearchActions(array $searchActions, array $filters): void
    {
        // TODO: 写法待完善

        if ($this->hasCustomSearchAction('search:managed_patients')) {
            $this->queryEngine->whereField(AdminContext::instance()->getAdminId(), 't2.manage_doctor_code');
        }

        if ($this->hasCustomSearchAction('search:assigned_patients')) {
            $this->queryEngine->whereField(AdminContext::instance()->getAdminId(), 't2.assign_manage_doctor_code');
        }

        if ($this->hasCustomSearchAction('search:follow_patients')) {
            $followTable = config('low-code.bmo-baseline.database.crowd-follow-table', '');

            $this->queryEngine->getQueryBuilder()
                ->whereExists(fn (Builder $query) => $query->from($followTable, 't20')
                    ->selectRaw('1')
                    ->whereRaw('t20.empi = t2.empi')
                    ->where('follower', AdminContext::instance()->getAdminId())
                    ->where('status', 1)
                    ->where('is_deleted', 0)
                );
        }

        if ($this->hasCustomSearchAction('search:patient_tags')) {
            $patientTagTable = config('low-code.bmo-baseline.database.crowd-patient-tag-table', '');

            $tagIds = $this->getCustomSearchActionParams('search:patient_tags');

            if (!empty($tagIds)) {
                $this->queryEngine->getQueryBuilder()
                    ->whereExists(fn (Builder $query) => $query->from($patientTagTable, 't30')
                        ->selectRaw('1')
                        ->whereRaw('t30.empi COLLATE utf8mb4_unicode_ci = t2.empi')
                        ->whereIn('tag_id', (array) $tagIds)
                        ->where('is_deleted', 0)
                    );
            }
        }
    }
}
