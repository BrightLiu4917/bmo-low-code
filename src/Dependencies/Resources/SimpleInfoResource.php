<?php

namespace BrightLiu\LowCode\Dependencies\Resources;

use BrightLiu\LowCode\Resources\Resident\ResidentArchive\InfoResource;
use BrightLiu\LowCode\Support\Attribute\Conversion;
use BrightLiu\LowCode\Support\Attribute\Converters\Age;
use BrightLiu\LowCode\Support\Attribute\Converters\BthDt;
use BrightLiu\LowCode\Support\Attribute\Converters\CurrAddr;
use BrightLiu\LowCode\Support\Attribute\Converters\GdrCd;
use BrightLiu\LowCode\Support\Attribute\Converters\HeightArrHeight;
use BrightLiu\LowCode\Support\Attribute\Converters\IdCrdNo;
use BrightLiu\LowCode\Support\Attribute\Converters\NtnCd;
use BrightLiu\LowCode\Support\Attribute\Converters\RegisAddr;
use BrightLiu\LowCode\Support\Attribute\Converters\SlfTelNo;
use BrightLiu\LowCode\Support\Attribute\Converters\WeightArrWeight;

/**
 * BrightLiu\LowCode\Resources\Resident\ResidentArchive\InfoResource 简易版
 */
class SimpleInfoResource extends InfoResource
{
    protected function fetchConversion(): Conversion
    {
        return Conversion::make([
            Age::class,
            BthDt::class,
            GdrCd::class,
            IdCrdNo::class,
            SlfTelNo::class,
            NtnCd::class,
            HeightArrHeight::class,
            WeightArrWeight::class,
            RegisAddr::class,
            CurrAddr::class,
        ]);
    }

    /**
     * 白名单
     * PS: 优先级高于黑名单
     */
    protected function fillable(): ?array
    {
        return [
            'rsdnt_nm',
            'gdr_cd',
            'age',
            'slf_tel_no',
            'id_crd_no',
            'bth_dt',
            'curr_addr',
            'ctct_nm',
            'ctct_tel_no',
            'regis_addr',
            'mdc_hst_infmt_past_hst',
            'mdc_hst_infmt_algn_hst',
            'mdc_hst_infmt_oprt_hst',
            'mdc_hst_infmt_fml_hst',
        ];
    }
}
