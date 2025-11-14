<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Support\Attribute\Converters;

use BrightLiu\LowCode\Support\Attribute\Foundation\Converter;

class RegisAddr extends Converter
{
    public function metadata(): array
    {
        return [
            'regis_addr_cnty_cd' => $this->getAttributeValue('regis_addr_cnty_cd'),
            'regis_addr_prv_cd' => $this->getAttributeValue('regis_addr_prv_cd'),
            'regis_addr_cty_cd' => $this->getAttributeValue('regis_addr_cty_cd'),
            'regis_addr_twn_cd' => $this->getAttributeValue('regis_addr_twn_cd'),
            'regis_addr_vlg_cd' => $this->getAttributeValue('regis_addr_vlg_cd'),
            'regis_addr_cnty_nm' => $this->getAttributeValue('regis_addr_cnty_nm'),
            'regis_addr_prv_nm' => $this->getAttributeValue('regis_addr_prv_nm'),
            'regis_addr_cty_nm' => $this->getAttributeValue('regis_addr_cty_nm'),
            'regis_addr_twn_nm' => $this->getAttributeValue('regis_addr_twn_nm'),
            'regis_addr_vlg_nm' => $this->getAttributeValue('regis_addr_vlg_nm'),
        ];
    }
}
