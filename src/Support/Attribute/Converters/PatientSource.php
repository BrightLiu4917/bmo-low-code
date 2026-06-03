<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Support\Attribute\Converters;

use BrightLiu\LowCode\Support\Attribute\Foundation\Converter;

class PatientSource extends Converter
{
    public function variant(): mixed
    {
        $value = parent::variant();

        if (empty($value)) {
            return '';
        }

        return $this->resolveEnumVariant(intval($value), [
            1 => '后台建档',
            2 => '数据采集',
            3 => '院外义诊',
            4 => '居民注册',
        ], '--');
    }
}
