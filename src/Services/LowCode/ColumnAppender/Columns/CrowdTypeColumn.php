<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\LowCode\ColumnAppender\Columns;

use BrightLiu\LowCode\Services\LowCode\ColumnAppender\IColumn;
use BrightLiu\LowCode\Services\QueryEngineService;
use Illuminate\Support\Collection;

class CrowdTypeColumn extends BasicColumn implements IColumn
{
    public function preload(QueryEngineService $queryEngine, Collection $items): mixed
    {
        if (empty($empis = $items->pluck('empi')->unique()->toArray())) {
            return $items;
        }

        $featureCrowdTable = config('low-code.bmo-baseline.database.crowd-type-table', '');
        $userGroupTable = config('low-code.bmo-baseline.database.crowd-group-table', 'user_group');

        // 按empi连表查询人群分类信息(一个人可能属于多个人群分类)
        return $queryEngine->useTable($featureCrowdTable . ' as t1')
            ->innerJoin($userGroupTable . ' as t2', 't2.id', '=', 't1.group_id')
            ->getQueryBuilder()
            ->whereIn('t1.empi', $empis)
            ->where('t2.is_deleted', 0)
            ->select(['t1.empi', 't2.group_name', 't2.select_type'])
            ->get()
            ->groupBy('empi');
    }

    public function handleItem($item, $sources): mixed
    {
        if (!isset($sources[$item->empi])) {
            return null;
        }

        return collect($sources[$item->empi] ?? null)
             // select_type=9为基线人群，排除再外
            ->where('select_type', '<>', 9)
            ->pluck('group_name')
            ->unique()
            ->join(',');
    }

    public static function columnName(): string
    {
        return '_crowds';
    }

    public static function metadata(): array
    {
        return [
            'group_id' => 'preset',
            'group_name' => '人群信息',
            'id' => 'preset_crowds',
            'name' => '人群分类',
            'type' => 'array',
            'column' => '_crowds',
        ];
    }
}
