<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Support\Attribute\Converters;

use BrightLiu\LowCode\Support\Attribute\Foundation\Converter;

class GdrCd extends Converter
{
    public function variant(): mixed
    {
        $value = parent::variant();

        return match (intval($value)) {
            9 => '未知',
            2 => '女',
            1 => '男',
            default => ''
        };
    }
}
