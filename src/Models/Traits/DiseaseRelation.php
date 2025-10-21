<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Models\Traits;

use App\Models\LowCode\LowCodeDisease;
use BrightLiu\LowCode\Context\DiseaseContext;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Model;

/**
 * @method QueryBuilder|EloquentBuilder|static|$this byContextDisease() 按 病种上下文 查询
 * @method QueryBuilder|EloquentBuilder|static|$this byDisease(string $diseaseCode) 按 病种code 查询
 * @property string $disease_code 病种code
 * @property string $disease_name 病种名称
 */
trait DiseaseRelation
{
    /**
     * @return void
     */
    protected static function bootDiseaseRelation()
    {
        if (!static::bootDiseaseRelationEnabled()) {
            return;
        }

        /** @phpstan-ignore-next-line */
        static::creating(function ($model) {
            if (empty($model->disease_code)) {
                $model->disease_code = DiseaseContext::instance()->getDiseaseCode();
            }
        });
    }

    /**
     * @return bool
     */
    protected static function bootDiseaseRelationEnabled(): bool
    {
        return true;
    }

    /**
     * 疾种
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function disease(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        /** @var Model $this */
        return $this->belongsTo(LowCodeDisease::class, 'disease_code', 'code');
    }

    /**
     * 按 病种上下文 查询
     *
     * @param EloquentBuilder $query
     *
     * @return EloquentBuilder
     */
    public function scopeByContextDisease(EloquentBuilder $query): EloquentBuilder
    {
        return $query->where('disease_code', DiseaseContext::instance()->getDiseaseCode());
    }

    /**
     * 按 病种code 查询
     *
     * @param EloquentBuilder $query
     * @param string $diseaseCode
     *
     * @return EloquentBuilder
     */
    public function scopeByDisease(EloquentBuilder $query, string $diseaseCode): EloquentBuilder
    {
        return $query->where('disease_code', $diseaseCode);
    }

    /**
     * 获取 疾种code
     *
     * @return string
     */
    public function getDiseaseCode(): string
    {
        return $this->disease_code;
    }

    /**
     * 访问器: 病种名称
     *
     * @return Attribute
     */
    public function diseaseName(): Attribute
    {
        return Attribute::get(fn () => $this->disease->name ?? '');
    }
}
