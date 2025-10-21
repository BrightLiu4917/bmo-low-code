<?php

declare(strict_types = 1);

namespace  BrightLiu\LowCode\Models\Traits;

trait OrgRelationQueries
{
    use OrgRelation;

    /**
     * @return bool
     */
    protected static function bootOrgRelationEnabled(): bool
    {
        return false;
    }
}
