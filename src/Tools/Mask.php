<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Tools;

use Illuminate\Support\Str;

final class Mask
{
    /**
     * 字符串脱敏
     *
     * @param string $value
     * @param null|int $index
     * @param null|int $maxLength
     *
     * @return string
     */
    public static function str(string $value, ?int $index = null, ?int $maxLength = null): string
    {
        $index = $index ?? 1;
        $length = $maxLength ?? max(1, mb_strlen($value) - 2);

        return Str::mask($value, '*', $index, $length);
    }

    /**
     * 姓名脱敏
     *
     * @param string $value
     *
     * @return string
     */
    public static function name(string $value): string
    {
        return self::str($value, 1);
    }

    /**
     * 身份证号脱敏
     *
     * @param string $value
     *
     * @return string
     */
    public static function idcard(string $value): string
    {
        return self::str($value, 4, mb_strlen($value) > 15 ? 9 : 6);
    }

    /**
     * 手机号脱敏
     *
     * @param string $value
     *
     * @return string
     */
    public static function phone(string $value): string
    {
        return self::str($value, 3, 4);
    }
}
