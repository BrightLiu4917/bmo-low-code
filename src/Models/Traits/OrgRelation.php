<?php

declare(strict_types = 1);

namespace  BrightLiu\LowCode\Models\Traits;

use BrightLiu\LowCode\Context\OrgContext;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @method QueryBuilder|EloquentBuilder|static|$this byContextOrg() 按 机构上下文 查询
 * @method QueryBuilder|EloquentBuilder|static|$this byOrg(string $orgCode) 按 机构code 查询
 * @property string $disease_code 病种code
 * @property string $disease_name 病种名称
 */
trait OrgRelation
{
    /**
     * @return void
     */
    protected static function bootOrgRelation()
    {
        if (!static::bootOrgRelationEnabled()) {
            return;
        }

        static::creating(function ($model) {
            if (empty($model->org_code)) {
                $model->org_code = OrgContext::instance()->getOrgCode();
            }
        });
    }

    /**
     * @return bool
     */
    protected static function bootOrgRelationEnabled(): bool
    {
        return true;
    }

    /**
     * 按 机构上下文 查询
     *
     * @param EloquentBuilder $query
     *
     * @return EloquentBuilder
     */
    public function scopeByContextOrg(EloquentBuilder $query): EloquentBuilder
    {
        return $query->where('org_code', OrgContext::instance()->getOrgCode());
    }

    /**
     * 按 机构code 查询
     *
     * @param EloquentBuilder $query
     * @param string $orgCode
     *
     * @return EloquentBuilder
     */
    public function scopeByOrg(EloquentBuilder $query, string $orgCode): EloquentBuilder
    {
        return $query->where('org_code', $orgCode);
    }
}
