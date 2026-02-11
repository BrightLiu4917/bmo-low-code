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

        // 是否关联场景表进行查询(count查询时通常不需要)
        $hasBizSceneFilter = true;
        if ($this->isQueryCount) {
            // TODO: 写法待完善
            // 遍历filters所有键，如果无t2关键字，则不关联t2表

            $exists = false;
            foreach (Arr::dot($filters) as $key => $value) {
                if (
                    str_contains((string) $value, 't2.') ||
                    str_contains((string) $value, '`t2`.') ||
                    str_contains((string) $key, 't2.') ||
                    str_contains((string) $key, '`t2`.')
                ) {
                    $exists = true;
                    break;
                }
            }

            $hasBizSceneFilter = $exists;
        }

        // 构建基本的关联查询
        $this->queryEngine->useTable($crowdTable . ' as t3')
            ->innerJoin($widthTable . ' as t1', 't3.empi', '=', 't1.empi');

        if ($hasBizSceneFilter) {
            $this->queryEngine
                ->innerJoin($this->bizSceneTable . ' as t2', 't3.empi', '=', 't2.empi')
                ->select(['t2.*', 't1.*']);
        } else  {
            $this->queryEngine
                ->select(['t1.*']);
        }
    }

    /**
     * 应用过滤条件
     */
    public function applyFilters(array $filters): void
    {
        $this->queryEngine->whereMixed($filters);
    }
}
