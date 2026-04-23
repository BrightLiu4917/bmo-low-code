<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Support\Attribute\Converters;

use BrightLiu\LowCode\Support\Attribute\Foundation\Converter;

class ExrcRcdExrcFrqCd extends Converter
{
    public function variant(): mixed
    {
        $value = parent::variant();

        return match (intval($value)) {
            3 => '偶尔',
            31 => '是',
            32 => '少于1次/月',
            4 => '不运动',
            default => ''
        };
    }
}
