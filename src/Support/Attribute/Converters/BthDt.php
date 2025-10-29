<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Support\Attribute\Converters;

use BrightLiu\LowCode\Support\Attribute\Foundation\Converter;
use Illuminate\Support\Carbon;

class BthDt extends Converter
{
    public function variant(): mixed
    {
        $value = parent::variant();

        return !empty($value) ? transform($value, fn ($value) => Carbon::make($value)->format('Y-m-d'), '') : '';
    }
}
