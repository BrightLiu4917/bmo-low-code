<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Support\Attribute\Converters;

use BrightLiu\LowCode\Support\Attribute\Foundation\Converter;

class CurrAddr extends Converter
{
    /**
     * 组合地址字段，非实际数据库字段，不参与 API 字段元信息获取
     */
    public static function fetchFieldMeta(): bool
    {
        return false;
    }

    protected function extraMetadata(): array
    {
        return [
            'curr_addr_cnty_cd' => $this->getAttributeValue('curr_addr_cnty_cd'),
            'curr_addr_prv_cd' => $this->getAttributeValue('curr_addr_prv_cd'),
            'curr_addr_cty_cd' => $this->getAttributeValue('curr_addr_cty_cd'),
            'curr_addr_twn_cd' => $this->getAttributeValue('curr_addr_twn_cd'),
            'curr_addr_vlg_cd' => $this->getAttributeValue('curr_addr_vlg_cd'),
            'curr_addr_cnty_nm' => $this->getAttributeValue('curr_addr_cnty_nm'),
            'curr_addr_prv_nm' => $this->getAttributeValue('curr_addr_prv_nm'),
            'curr_addr_cty_nm' => $this->getAttributeValue('curr_addr_cty_nm'),
            'curr_addr_twn_nm' => $this->getAttributeValue('curr_addr_twn_nm'),
            'curr_addr_vlg_nm' => $this->getAttributeValue('curr_addr_vlg_nm'),
        ];
    }
}
