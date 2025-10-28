<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Support\Attribute\Converters;

use BrightLiu\LowCode\Support\Attribute\Foundation\Converter;
use BrightLiu\LowCode\Tools\Mask;

class SlfTelNo extends Converter
{
    public function variant(): mixed
    {
        $value = parent::variant();

        return !empty($value) ? Mask::phone((string) $value) : '';
    }
}
