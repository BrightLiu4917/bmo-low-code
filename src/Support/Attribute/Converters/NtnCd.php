<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Support\Attribute\Converters;

use BrightLiu\LowCode\Support\Attribute\Foundation\Converter;

class NtnCd extends Converter
{
    public function variant(): mixed
    {
        $value = $this->getValue();

        $mapping = '
            [
                { "col_nm": "汉族", "col_value": "01" },
                { "col_nm": "蒙古族", "col_value": "02" },
                { "col_nm": "回族", "col_value": "03" },
                { "col_nm": "藏族", "col_value": "04" },
                { "col_nm": "维吾尔族", "col_value": "05" },
                { "col_nm": "苗族", "col_value": "06" },
                { "col_nm": "彝族", "col_value": "07" },
                { "col_nm": "壮族", "col_value": "08" },
                { "col_nm": "布依族", "col_value": "09" },
                { "col_nm": "朝鲜族", "col_value": "10" },
                { "col_nm": "满族", "col_value": "11" },
                { "col_nm": "侗族", "col_value": "12" },
                { "col_nm": "瑶族", "col_value": "13" },
                { "col_nm": "白族", "col_value": "14" },
                { "col_nm": "土家族", "col_value": "15" },
                { "col_nm": "哈尼族", "col_value": "16" },
                { "col_nm": "哈萨克族", "col_value": "17" },
                { "col_nm": "傣族", "col_value": "18" },
                { "col_nm": "黎族", "col_value": "19" },
                { "col_nm": "傈僳族", "col_value": "20" },
                { "col_nm": "佤族", "col_value": "21" },
                { "col_nm": "畲族", "col_value": "22" },
                { "col_nm": "高山族", "col_value": "23" },
                { "col_nm": "拉祜族", "col_value": "24" },
                { "col_nm": "水族", "col_value": "25" },
                { "col_nm": "东乡族", "col_value": "26" },
                { "col_nm": "纳西族", "col_value": "27" },
                { "col_nm": "景颇族", "col_value": "28" },
                { "col_nm": "柯尔克孜族", "col_value": "29" },
                { "col_nm": "土族", "col_value": "30" },
                { "col_nm": "达斡尔族", "col_value": "31" },
                { "col_nm": "仫佬族", "col_value": "32" },
                { "col_nm": "羌族", "col_value": "33" },
                { "col_nm": "布朗族", "col_value": "34" },
                { "col_nm": "撒拉族", "col_value": "35" },
                { "col_nm": "毛南族", "col_value": "36" },
                { "col_nm": "仡佬族", "col_value": "37" },
                { "col_nm": "锡伯族", "col_value": "38" },
                { "col_nm": "阿昌族", "col_value": "39" },
                { "col_nm": "普米族", "col_value": "40" },
                { "col_nm": "塔吉克族", "col_value": "41" },
                { "col_nm": "怒族", "col_value": "42" },
                { "col_nm": "乌孜别克族", "col_value": "43" },
                { "col_nm": "俄罗斯族", "col_value": "44" },
                { "col_nm": "鄂温克族", "col_value": "45" },
                { "col_nm": "德昴族", "col_value": "46" },
                { "col_nm": "保安族", "col_value": "47" },
                { "col_nm": "裕固族", "col_value": "48" },
                { "col_nm": "京族", "col_value": "49" },
                { "col_nm": "塔塔尔族", "col_value": "50" },
                { "col_nm": "独龙族", "col_value": "51" },
                { "col_nm": "鄂伦春族", "col_value": "52" },
                { "col_nm": "赫哲族", "col_value": "53" },
                { "col_nm": "门巴族", "col_value": "54" },
                { "col_nm": "珞巴族", "col_value": "55" },
                { "col_nm": "基诺族", "col_value": "56" },
                { "col_nm": "其他族", "col_value": "9999" }
            ]
        ';

        $mapping = collect(json_decode($mapping, true))->mapWithKeys(fn ($item) => [intval($item['col_value']) => $item['col_nm']]);

        return $mapping[$value] ?? $value;
    }
}
