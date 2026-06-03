<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Support\Attribute\Converters;

use BrightLiu\LowCode\Support\Attribute\Foundation\Converter;
use BrightLiu\LowCode\Tools\Human;

class Age extends Converter
{
    /**
     * 计算字段，非实际数据库字段，不参与 API 字段元信息获取
     */
    public static function fetchFieldMeta(): bool
    {
        return false;
    }

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
