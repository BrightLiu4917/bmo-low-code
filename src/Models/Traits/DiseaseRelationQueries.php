<?php

declare(strict_types = 1);

namespace  BrightLiu\LowCode\Models\Traits;

trait DiseaseRelationQueries
{
    use DiseaseRelation;

    /**
     * @return bool
     */
    protected static function bootDiseaseRelationEnabled(): bool
    {
        return false;
    }
}
