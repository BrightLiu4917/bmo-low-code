<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Support\Attribute\Converters;

use BrightLiu\LowCode\Support\Attribute\Foundation\Converter;

class DrnkRcdDrnkFlg extends Converter
{
    public function variant(): mixed
    {
        $value = parent::variant();

        return match (intval($value)) {
            1 => '从不吸烟',
            2 => '过去吸，已戒烟',
            3 => '吸烟',
            default => ''
        };
    }
}
