<?php

declare(strict_types=1);

namespace  BrightLiu\LowCode\Models\Traits;

trait OrgDiseaseRelationQueries
{
    use OrgRelationQueries,DiseaseRelationQueries;

    /**
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder|static|$this
     */
    public static function contextQuery()
    {
        return static::query()->byContextOrg()->byContextDisease();
    }
}
