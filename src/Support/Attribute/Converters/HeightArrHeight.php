<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Support\Attribute\Converters;

use BrightLiu\LowCode\Support\Attribute\Foundation\Converter;

class HeightArrHeight extends Converter
{
    public function unit(): string
    {
        return 'cm';
    }
}
