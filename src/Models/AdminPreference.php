<?php

declare(strict_types=1);

namespace App\Models\LowCode;

use BrightLiu\LowCode\Models\Traits\OrgDiseaseRelation;

/**
 * @Class
 * @Description: 管理员偏好设置
 * @created    : 2025-10-02 14:37:58
 * @modifier   : 2025-10-02 14:37:58
 */
final class AdminPreference extends LowCodeBaseModel
{
    use OrgDiseaseRelation;

    protected $casts = [
        'pvalue' => 'json',
    ];
}
