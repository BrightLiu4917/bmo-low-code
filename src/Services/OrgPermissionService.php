<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Services;

use BrightLiu\LowCode\Traits\Context\WithContext;

/**
 * @Class
 * @Description:机构权限服务
 * @created: 2025-10-29 20:27:18
 * @modifier: 2025-10-29 20:27:18
 */
class OrgPermissionService extends LowCodeBaseService
{
    use WithContext;

    public function formatOrg():array
    {
        return $this->getManageOrgCode();
    }
}