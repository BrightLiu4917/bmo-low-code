<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Support\Attribute\Converters;

use BrightLiu\LowCode\Support\Attribute\Foundation\Converter;

/**
 * 将 manage_proj_id 转为 string，避免超出 JSON 安全整数导致前端精度丢失
 */
class ManageProjId extends Converter
{
    public function value(): mixed
    {
        $value = parent::value();

        if ($value === null || $value === '') {
            return $value;
        }

        return (string) $value;
    }
}
