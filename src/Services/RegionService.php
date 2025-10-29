<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Services;



use BrightLiu\LowCode\Tools\Region;
use BrightLiu\LowCode\Traits\Context\WithContext;

/**
 * @Class
 * @Description:
 * @created: 2025-10-29 20:27:18
 * @modifier: 2025-10-29 20:27:18
 */
class RegionService extends LowCodeBaseService
{
    use WithContext;
    public static function getBatchRegionLevel(array $codes = []):array
    {
        // 初始化返回结构
        $result = [
            'prv'  => [],
            'cty'  => [],
            'cnty' => [],
            'twn'  => [],
            'vlg'  => [],
        ];

        if (empty($codes)) {
            return $result;
        }
        foreach ($codes as $code) {
            $level = Region::regionLevel($code);
            // 只有当regionLevel返回非空字符串时才添加
            if (!empty($level) && isset($result[$level])) {
                $result[$level][] = $code;
            }
        }
        return $result;
    }
}
