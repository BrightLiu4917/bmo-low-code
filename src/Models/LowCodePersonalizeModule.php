<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Models;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\SoftDeletes;
use BrightLiu\LowCode\Models\Traits\ModelFetch;
use BrightLiu\LowCode\Models\Traits\DiseaseRelation;
use BrightLiu\LowCode\Models\Traits\Cacheable\CacheableModel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use BrightLiu\LowCode\Models\Traits\UniqueCodeRelation;
use BrightLiu\LowCode\Models\Traits\AdministratorRelation;
use BrightLiu\LowCode\Models\Traits\Cacheable\NewEloquentBuilder;
use BrightLiu\LowCode\Models\Traits\SceneRelation;

/**
 * @Class
 * @Description:
 * @created    : 2025-10-02 13:07:23
 * @modifier   : 2025-10-02 13:07:23
 */
final class LowCodePersonalizeModule extends LowCodeBaseModel
{
    use DiseaseRelation, SceneRelation;

    protected $table = 'personalize_modules';


    public const UPDATED_AT = null;

    protected $casts = [
        'metadata' => 'json',
        'module_ids' => 'array',
        'module_meta' => 'array',
    ];

    /**
     * @return array<int, string>
     */
    public function resolveCrowdIds(): array
    {
        $ids = self::normalizeCrowdIds((array) ($this->module_ids ?? []));

        return $ids !== [] ? $ids : self::normalizeCrowdIds([(string) ($this->module_id ?? '')]);
    }

    /**
     * @param array<int, mixed> $ids
     * @return array<int, string>
     */
    public static function normalizeCrowdIds(array $ids): array
    {
        return array_values(array_unique(array_filter(
            array_map(static fn ($id) => trim((string) $id), $ids),
            static fn (string $id) => '' !== $id
        )));
    }
}
