<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Tools;

use Illuminate\Support\Str;

/**
 * @Class
 * @Description: 行政区域层级 与用户中心对接
 * @created: 2025-10-29 20:25:57
 * @modifier: 2025-10-29 20:25:57
 */
final class Region
{
    /**
     * 获取行政区划编码的级别
     * @param string $code
     * @return string
     */
    public static function regionLevel(string $code = ''): string
    {
        if (empty($code)) {
            return '';
        }

        // 优先读取本地的地区配置
        if (!empty(config("business.region"))) {
            return self::regionLevelByRegionConf($code);
        }

        if (strlen($code) !== 12) {
            return '';
        }

        // 警告：这个判断规则不准确，仅作为没有配置时的兜底
        $zeros = strlen($code) - strlen(rtrim($code, '0'));
        return match ($zeros) {
            10      => 'prv',     // 省级：末尾10个0，如 110000000000
            8       => 'cty',     // 市级：末尾8个0，如 110100000000
            6       => 'cnty',    // 区县级：末尾6个0，如 110101000000
            3       => 'twn',     // 乡镇级：末尾3个0，如 110101001000
            1,0     => 'vlg',     // 村级：无末尾0，如 110101001001
            default => ''
        };
    }


    /**
     * 获取行政区划编码的级别：通过本地的配置文件
     * @param string $code
     * @return string
     */
    public static function regionLevelByRegionConf(string $code = ''): string
    {
        if (empty($code)) {
            return '';
        }

        $regions = config("business.region.{$code}");
        if (empty($regions)) {
            return '';
        }

        return match ($regions['type'] ?? '') {
            'province'  => 'prv',     // 省级
            'city'      => 'cty',     // 市级
            'district'  => 'cnty',    // 区县级
            'town'      => 'twn',     // 乡镇级
            'community' => 'vlg',     // 村级
            default     => ''
        };
    }
}
