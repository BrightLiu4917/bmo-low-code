<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\LowCode\ColumnAppender\Columns;

use BrightLiu\LowCode\Services\LowCode\ColumnAppender\IColumn;
use BrightLiu\LowCode\Services\QueryEngineService;
use BrightLiu\LowCode\Traits\Context\WithContext;
use Illuminate\Support\Collection;

class PatientTagsColumn extends BasicColumn implements IColumn
{
    use WithContext;

    public function preload(QueryEngineService $queryEngine, Collection $items): mixed
    {
        if (empty($empis = $items->pluck('empi')->unique()->toArray())) {
            return $items;
        }

        $patientTagTable = config('low-code.bmo-baseline.database.crowd-patient-tag-table', '');

        return $queryEngine->useTable($patientTagTable)
            ->getQueryBuilder()
            ->whereIn('empi', $empis)
            ->where('is_deleted', 0)
            ->where('scene_code', $this->getSceneCode())
            ->where('disease_code', $this->getDiseaseCode())
            ->where('org_code', $this->getAffiliatedOrgCode())
            ->get(['empi', 'tag_name', 'tag_id'])
            ->groupBy('empi');
    }

    public function handleItem($item, $sources): mixed
    {
        if (!isset($sources[$item->empi])) {
            return null;
        }

        return collect($sources[$item->empi] ?? null)->map(fn ($item) => [
            'label' => $item->tag_name,
            'value' => $item->tag_id,
        ])->toArray();
    }

    public function handleItemVariant($item, $sources, $value): mixed
    {
        if (empty($value)) {
            return null;
        }

        return implode(',', array_column($value, 'label'));
    }

    public static function columnName(): string
    {
        return '_patient_tags';
    }

    public static function metadata(): array
    {
        return [
            'group_id' => 'preset',
            'group_name' => '人群信息',
            'id' => 'preset_patient_tags',
            'name' => '患者标签',
            'type' => 'array',
            'column' => '_patient_tags',
        ];
    }
}
