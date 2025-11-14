<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Support\Attribute\Converters;

use BrightLiu\LowCode\Support\Attribute\Foundation\Converter;

class CurrAddr extends Converter
{
    public function metadata(): array
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
