<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Models;

use BrightLiu\LowCode\Models\Traits\OrgDiseaseRelation;
use BrightLiu\LowCode\Models\Traits\SceneRelation;

/**
 * @Class
 * @Description: 管理员偏好设置
 * @created    : 2025-10-02 14:37:58
 * @modifier   : 2025-10-02 14:37:58
 */
final class AdminPreference extends LowCodeBaseModel
{
    use OrgDiseaseRelation, SceneRelation;

    protected $casts = [
        'pvalue' => 'json',
    ];
}
