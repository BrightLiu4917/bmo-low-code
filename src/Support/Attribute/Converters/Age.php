<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Support\Attribute\Converters;

use BrightLiu\LowCode\Support\Attribute\Foundation\Converter;
use BrightLiu\LowCode\Tools\Human;

class Age extends Converter
{
    public function value(): mixed
    {
        $value = parent::value();

        $idCrdNo = $this->getAttributeValue('id_crd_no');

        return max(empty($value) && !empty($idCrdNo) ? Human::getIdcardAge((string) $idCrdNo) : $value, 1);
    }

    public function unit(): string
    {
        return '岁';
    }
}
