<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Models\Traits;

use BrightLiu\LowCode\Context\DiseaseContext;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

trait SceneRelation
{
    /**
     * @return void
     */
    protected static function bootSceneRelation()
    {
        if (!static::bootSceneRelationEnabled()) {
            return;
        }

        /** @phpstan-ignore-next-line */
        static::creating(function ($model) {
            if (empty($model->scene_code)) {
                $model->scene_code = DiseaseContext::instance()->getSceneCode();
            }
        });
    }

    protected static function bootSceneRelationEnabled(): bool
    {
        return true;
    }

    /**
     * 按 场景上下文 查询
     */
    public function scopeByContextScene(EloquentBuilder $query): EloquentBuilder
    {
        return $query->where('scene_code', DiseaseContext::instance()->getSceneCode());
    }

    /**
     * 按 场景code 查询
     */
    public function scopeByScene(EloquentBuilder $query, string $sceneCode): EloquentBuilder
    {
        return $query->where('scene_code', $sceneCode);
    }
}
