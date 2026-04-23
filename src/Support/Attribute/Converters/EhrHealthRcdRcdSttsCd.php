<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Support\Attribute\Converters;

use BrightLiu\LowCode\Support\Attribute\Foundation\Converter;

class EhrHealthRcdRcdSttsCd extends Converter
{
    public function variant(): mixed
    {
        $value = parent::variant();

        return match (intval($value)) {
            0 => '正常',
            1 => '已注销',
            2 => '未审核',
            default => ''
        };
    }
}
