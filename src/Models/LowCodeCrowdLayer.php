<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Models;

use BrightLiu\LowCode\Models\Traits\DiseaseRelation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LowCodeCrowdLayer extends LowCodeBaseModel
{
    use DiseaseRelation;

    public const UPDATED_AT = null;

    protected $casts = [
        'preset_filters' => 'array',
    ];

    public function personalizeModule(): BelongsTo
    {
        return $this->belongsTo(LowCodePersonalizeModule::class, 'module_id');
    }
}
