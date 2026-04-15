<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\LowCode\ColumnAppender\Columns;

use BrightLiu\LowCode\Services\CrowdKitService;
use BrightLiu\LowCode\Services\LowCode\ColumnAppender\IColumn;
use BrightLiu\LowCode\Services\QueryEngineService;
use BrightLiu\LowCode\Traits\Context\WithOrgContext;
use Illuminate\Support\Collection;

class CrowdTypeColumn extends BasicColumn implements IColumn
{
    use WithOrgContext;

    public function preload(QueryEngineService $queryEngine, Collection $items): mixed
    {
        if (empty($empis = $items->pluck('empi')->unique()->toArray())) {
            return $items;
        }

        return collect(CrowdKitService::instance()->batchGetCrowdTypes($empis));
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
