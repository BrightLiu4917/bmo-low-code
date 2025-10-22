<?php

namespace BrightLiu\LowCode\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use BrightLiu\LowCode\Models\Traits\Cacheable\NewEloquentBuilder;
use BrightLiu\LowCode\Enums\Model\LowCode\LowCodeList\ListTypeEnum;
use BrightLiu\LowCode\Models\Traits\OrgDiseaseRelation;
use BrightLiu\LowCode\Models\Traits\OrgRelation;
use BrightLiu\LowCode\Models\Traits\Cacheable\CacheableModel;
use BrightLiu\LowCode\Models\Traits\UniqueCodeRelation;
use BrightLiu\LowCode\Models\Traits\AdministratorRelation;


/**
 * @Class
 * @Description:
 * @created    : 2025-10-01 11:51:47
 * @modifier   : 2025-10-01 11:51:47
 */
class DataPermission extends LowCodeBaseModel
{
    use
//        NewEloquentBuilder,
        SoftDeletes,
        UniqueCodeRelation,
        OrgDiseaseRelation,
        OrgRelation,
        CacheableModel;

}
