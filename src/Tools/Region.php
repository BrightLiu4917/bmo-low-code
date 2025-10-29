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
     * {
     *
     * "prv":[],
     * "cty":[],
     * "cnty":[],
     * "twn":[],
     * "vlg":[]
     *
     * }
     * @param $code
     *
     * @return string
     */
    public static function regionLevel(string $code = '')
    {
        if (empty($code)) {
            return '';
        }
        if (strlen($code) !== 12) {
            return '';
        }
        $zeros = strlen($code) - strlen(rtrim($code, '0'));
        return match ($zeros) {
            10      => 'prv',     // 省级：末尾10个0，如 110000000000
            8       => 'cty',     // 市级：末尾8个0，如 110100000000
            6       => 'cnty',    // 区县级：末尾6个0，如 110101000000
            3       => 'twn',     // 乡镇级：末尾3个0，如 110101001000
            0       => 'vlg',     // 村级：无末尾0，如 110101001001
            default => ''
        };
    }
}
