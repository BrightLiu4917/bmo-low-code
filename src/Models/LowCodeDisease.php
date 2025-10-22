<?php

declare(strict_types = 1);
namespace BrightLiu\LowCode\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use BrightLiu\LowCode\Models\Traits\ModelFetch;

/**
 * @Class
 * @Description: 疾病种类
 * @created    : 2025-10-01 19:15:09
 * @modifier   : 2025-10-01 19:15:09
 */
final class LowCodeDisease extends LowCodeBaseModel
{
    use
        SoftDeletes,
        ModelFetch;

    /**
     * @var string[]
     */
    protected $fillable
        = [
            'code',
            'name',
            'weight',
            'creator_id',
            'updater_id',
            'created_at',
            'updated_at',
            'deleted_at',
            'extraction_pattern',
        ];

    /**
     * @var string[]
     */
    protected $casts = [
        'name'               => 'string',
        'code'               => 'string',
        'weight'             => 'integer',
        'creator_id'         => 'integer',
        'updater_id'         => 'integer',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
        'deleted_at'         => 'datetime',
        'extraction_pattern' => 'string',
    ];
}
