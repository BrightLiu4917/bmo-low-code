<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\LowCode\QueryBuilder;

use BrightLiu\LowCode\Services\Contracts\ILowCodeQueryBuilder;
use Illuminate\Support\Arr;

/**
 * 针对mysql优化版
 *
 * PS： DefaultQueryBuilder适用与TiDB
 */
class MysqlQueryBuilder extends DefaultQueryBuilder implements ILowCodeQueryBuilder
{
    /**
     * 构建基本的关联查询
     */
    public function relationQueryEngine(array $filters): void
    {
        // 宽表 表名
        $widthTable = config('low-code.bmo-baseline.database.crowd-psn-wdth-table', '');

        // 人群表 表名
        $crowdTable = config('low-code.bmo-baseline.database.crowd-type-table', '');

        // 提取并转换“人群分类”条件作为标准的查询条件
        $crowdIdIndex = Arr::first(
            array_keys($filters),
            fn ($key) => isset($filters[$key][0]) && 'crowd_id' === $filters[$key][0]
        );
        if (!empty($conditionOfCrowd = $filters[$crowdIdIndex] ?? null)) {
            unset($filters[$crowdIdIndex]);
            $filters[] = ['t3.group_id', '=', $conditionOfCrowd[2]];
        }

        // 构建基本的关联查询
        $this->queryEngine->useTable($crowdTable . ' as t3')
            ->innerJoin($widthTable . ' as t1', 't3.empi', '=', 't1.empi')
            ->innerJoin($this->bizSceneTable . ' as t2', 't3.empi', '=', 't2.empi')
            ->select(['t2.*', 't1.*']);
    }

    /**
     * 应用过滤条件
     */
    public function applyFilters(array $filters): void
    {
        $this->queryEngine->whereMixed($filters);
    }
}
