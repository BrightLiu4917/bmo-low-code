<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Tools;

final class Human
{
    /**
     * 构建 USER_ID
     *
     * @param string $idCardNo 身份证号码
     *
     * @return string
     */
    public static function generateUserId(string $idCardNo): string
    {
        if (empty($idCardNo)) {
            return '';
        }

        return md5($idCardNo);
    }

    /**
     * 身份证号码校验
     *
     * @param string $value
     *
     * @return bool
     */
    public static function checkIdcard(string $value): bool
    {
        if (empty($value)) {
            return false;
        }

        $weight = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];

        $checksum = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];

        if (!preg_match('/^\d{17}[\d|X]$/i', $value)) {
            return false;
        }

        $sum = 0;

        for ($i = 0; $i < 17; $i++) {
            $sum += intval($value[$i]) * $weight[$i];
        }

        $mod = $sum % 11;

        return strcasecmp($checksum[$mod], $value[17]) === 0;
    }

    /**
     * 标准化证件
     * PS: 如残疾人证为20位
     *
     * @param string $value
     *
     * @return string
     */
    public static function standardizationCard(string $value): string
    {
        if (empty($value)) {
            return '';
        }

        $idcard = $value;

        // 老版身份证
        if (15 == mb_strlen($idcard)) {
            $W = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2, 1];
            $A = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];
            $s = 0;
            $idCard18 = mb_substr($idcard, 0, 6).'19'.mb_substr($idcard, 6);
            $idCard18Len = mb_strlen($idCard18);
            for ($i = 0; $i < $idCard18Len; $i++) {
                $s = $s + intval(mb_substr($idCard18, $i, 1)) * $W[$i];
            }
            $idCard18 .= $A[$s % 11];
            $idcard = $idCard18;
        } // 残疾证
        elseif (20 == mb_strlen($idcard)) {
            $idcard = mb_substr($idcard, 0, 18);
        }

        return $idcard;
    }

    /**
     * 获取身份证年龄
     *
     * @param string $idcard
     * @param bool   $isFullYear 是否为周岁
     *
     * @return int
     */
    public static function getIdcardAge(string $idcard, bool $isFullYear = true,
    ): int {
        $birth = self::getIdcardBirth($idcard);
        if (empty($birth)) {
            return 0;
        }

        try {
            return match (true) {
                $isFullYear => now()->diffInYears($birth),
                default     => (int)ceil(now()->floatDiffInYears($birth)),
            };
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * 获取身份证性别
     *
     * @param string $idcard
     *
     * @return string
     */
    public static function getIdcardGender(string $idcard): string
    {
        if (empty($idcard)) {
            return '';
        }

        $idcard = self::standardizationCard($idcard);

        return match (self::getIdcardGenderCode($idcard)) {
            0 => '女',
            1 => '男',
            default => ''
        };
    }

    /**
     * 获取身份证性别编码
     *
     * @param string $idcard
     *
     * @return int
     */
    public static function getIdcardGenderCode(string $idcard): int
    {
        if (empty($idcard)) {
            return -1;
        }

        $idcard = self::standardizationCard($idcard);

        return intval(($idcard[-2] ?? 0) % 2);
    }

    /**
     * 获取身份证出生日期
     *
     * @param string $idcard
     *
     * @return string
     */
    public static function getIdcardBirth(string $idcard): string
    {
        if (empty($idcard)) {
            return '';
        }

        $idcard = self::standardizationCard($idcard);

        $birth = '';
        $preg = "/^(\d{6})(\d{8})(\d{3}[0-9X])$/";
        if (preg_match($preg, $idcard, $matches)) {
            $birth = $matches[2];
        }

        return $birth;
    }

    /**
     * 获取身份证出生日期年份
     *
     * @param string $idcard
     *
     * @return int
     */
    public static function getIdcardBirthYear(string $idcard): int
    {
        $birth = self::getIdcardBirth($idcard);

        return (int)match (true) {
            !empty($birth) => mb_substr($birth, 0, 4),
            default        => 0
        };
    }

    /**
     * 获取身份证出生日期并格式化为 Y-m-d
     *
     * @param string $idcard 身份证号码
     *
     * @return string 格式化的日期(如:1999-09-09)或空字符串
     */
    public static function getFormattedBirthDate(string $idcard): string
    {
        if (empty($idcard)) {
            return '';
        }

        $idcard = self::standardizationCard($idcard);
        if (empty($idcard)) {
            return '';
        }

        // 18位身份证匹配: 6位地区码 + 4位年份 + 2位月 + 2位日 + 3位顺序码 + 1位校验码
        if (preg_match(
            '/^(\d{6})(\d{4})(\d{2})(\d{2})\d{3}[\dX]$/', $idcard, $matches
        )
        ) {
            $year = $matches[2];
            $month = $matches[3];
            $day = $matches[4];
        } // 15位身份证匹配: 6位地区码 + 2位年份(19XX) + 2位月 + 2位日 + 3位顺序码
        elseif (preg_match(
            '/^(\d{6})(\d{2})(\d{2})(\d{2})\d{3}$/', $idcard, $matches
        )
        ) {
            $year = '19'.$matches[2];
            $month = $matches[3];
            $day = $matches[4];
        } else {
            return '';
        }

        // 验证年份范围(1900-当前年份)
        $currentYear = (int)date('Y');
        $yearInt = (int)$year;
        if ($yearInt < 1900 || $yearInt > $currentYear) {
            return '';
        }

        // 验证日期有效性
        if (checkdate((int)$month, (int)$day, $yearInt)) {
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }

        return '';
    }
}
