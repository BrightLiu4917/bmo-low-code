<?php

declare(strict_types=1);

namespace App\Http\Resources\LowCode;

use BrightLiu\LowCode\Tools\Human;
use BrightLiu\LowCode\Tools\BetterArr;
use BrightLiu\LowCode\Tools\Mask;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

final class BasicInfoResource extends JsonResource
{
    /**
     * @param Request $request
     */
    public function toArray($request)
    {
        $info = (array)$this['info'];

        return [
            // 基本信息
            'id_crd_no' => Mask::idcard($info['id_crd_no'] ?? ''),
            'rsdnt_nm' => $info['rsdnt_nm'] ?? '',
            'user_id' => $info['user_id'] ?? '',
            'gdr_cd' => $info['gdr_cd'] ?? 0,
            'gdr_cd_nm' => match (intval($info['gdr_cd'] ?? 0)) {
                1 => '男',
                2 => '女',
                default => ''
            },
            'bth_dt' => transform($info['bth_dt'] ?? '', fn ($value) => Carbon::make($value)->format('Y-m-d')),
            'age' => max(!empty($info['age']) ? $info['age'] : Human::getIdcardAge($info['id_crd_no'] ?? ''), 1),
            'slf_tel_no' => $info['slf_tel_no'] ?? '',

            // 基本体征信息
            'height' => BetterArr::get($info, ['height_arr_height', 'height']),
            'weight' => BetterArr::get($info, ['weight_arr_weight', 'weight']),
            'bmi' => BetterArr::get($info, ['bmi_arr_bmi', 'bmi']),


            // 人群分类
            'crowds' => collect($this['crowds'] ?? [])
                ->map(fn ($crowd) => ['id' => $crowd->group_id ?? '', 'name' => $crowd->group_name ?? ''])
                ->toArray(),
        ];
    }
}