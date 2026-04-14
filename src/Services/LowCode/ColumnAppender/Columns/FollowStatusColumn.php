<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\LowCode\ColumnAppender\Columns;

use BrightLiu\LowCode\Services\LowCode\ColumnAppender\IColumn;
use BrightLiu\LowCode\Services\QueryEngineService;
use BrightLiu\LowCode\Traits\Context\WithContext;
use Illuminate\Support\Collection;

class FollowStatusColumn extends BasicColumn implements IColumn
{
    use WithContext;

    public function preload(QueryEngineService $queryEngine, Collection $items): mixed
    {
        if (empty($empis = $items->pluck('empi')->unique()->toArray())) {
            return $items;
        }

        $followTable = config('low-code.bmo-baseline.database.crowd-follow-table', '');

        // 按empi连表查询人群分类信息(一个人可能属于多个人群分类)
        return $queryEngine->useTable($followTable)
            ->getQueryBuilder()
            ->whereIn('empi', $empis)
            ->where('is_deleted', 0)
            ->where('scene_code', $this->getSceneCode())
            ->where('org_code', $this->getAffiliatedOrgCode())
            ->where('disease_code', $this->getDiseaseCode())
            ->where('follower', $this->getAdminId())
            ->where('status', 1)
            ->pluck('empi');
    }

    public function handleItem($item, $sources): mixed
    {
        return $sources->contains($item->empi ?? '') ? 1 : 0;
    }

    public function handleItemVariant($item, $sources, $value): mixed
    {
        return $value ? '已关注' : '未关注';
    }

    public static function columnName(): string
    {
        return '_follow_status';
    }

    public static function metadata(): array
    {
        return [
            'group_id' => 'preset',
            'group_name' => '人群信息',
            'id' => 'preset_follow_status',
            'name' => '关注状态',
            'type' => 'array',
            'column' => '_follow_status',
        ];
    }
}
