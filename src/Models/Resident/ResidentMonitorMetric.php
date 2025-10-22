<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Models\Resident;

use BrightLiu\LowCode\Models\Traits\DiseaseRelationQueries;
use Gupo\BetterLaravel\Database\BaseModel;

/**
 * 居民监测指标
 */
class ResidentMonitorMetric extends BaseModel
{
    use DiseaseRelationQueries;

    /**
     * 标记为禁止维护updated_at
     */
    public const UPDATED_AT = null;
}
