<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Support\Attribute\Converters;

use BrightLiu\LowCode\Support\Attribute\Foundation\Converter;

class EhrHealthRcdRcdOpnFlg extends Converter
{
    public function variant(): mixed
    {
        $value = parent::variant();

        return match (intval($value)) {
            0 => '否',
            1 => '是',
            default => ''
        };
    }
}
