<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Services;

use BrightLiu\LowCode\Models\DataPermission;
use BrightLiu\LowCode\Traits\Context\WithContext;

/**
 * @Class
 * @Description:权限服务
 * @created: 2025-10-29 20:27:18
 * @modifier: 2025-10-29 20:27:18
 */
class DataPermissionService extends LowCodeBaseService
{
    use WithContext;

//    protected string $channel = 'region';

    protected string $permissionKey = '';

    /**
     * 设置数据权限渠道
     * @param  string|null  $channel
     *
     * @return $this
     */
    public function channel(?string $channel = null):static
    {
        $this->channel = $channel ?? config('low_code.use-data-permission');
        return $this;
    }

    /**
     * @param  string  $values
     *
     * @return $this
     */
    public function setPermissionKey(string $values = '')
    {
        $this->permissionKey = $values;
        return $this;
    }

    /**
     * @return \BrightLiu\LowCode\Models\DataPermission|null
     */
    public function getAllPermission()
    {
       return DataPermission::getAllData();
    }
}