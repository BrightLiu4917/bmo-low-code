<?php

declare(strict_types = 1);

namespace App\Models\LowCode;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\SoftDeletes;
use BrightLiu\LowCode\Models\Traits\ModelFetch;
use BrightLiu\LowCode\Models\Traits\DiseaseRelation;
use BrightLiu\LowCode\Models\Traits\Cacheable\CacheableModel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use BrightLiu\LowCode\Models\Traits\UniqueCodeRelation;
use BrightLiu\LowCode\Models\Traits\AdministratorRelation;
use BrightLiu\LowCode\Models\Traits\Cacheable\NewEloquentBuilder;

/**
 * @Class
 * @Description:
 * @created    : 2025-10-02 13:07:23
 * @modifier   : 2025-10-02 13:07:23
 */
final class LowCodePersonalizeModule extends LowCodeBaseModel
{
    use DiseaseRelation;

    public const UPDATED_AT = null;

    protected $casts = [
        'metadata' => 'json',
    ];
}
