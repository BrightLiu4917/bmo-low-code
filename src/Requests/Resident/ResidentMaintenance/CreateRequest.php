<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Requests\Resident\ResidentMaintenance;

use Gupo\BetterLaravel\Rules\IdCardRule;
use Gupo\BetterLaravel\Rules\PhoneRule;
use Gupo\BetterLaravel\Validation\BaseRequest;

final class CreateRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'params.id_crd_no' => ['bail', 'required', 'string', new IdCardRule()],
            'params.slf_tel_no' => ['bail', 'required', 'string', new PhoneRule()],
            'params.rsdnt_nm' => ['bail', 'required', 'string', 'min:2', 'max:16'],
            'params.bth_dt' => ['bail', 'required', 'string', 'date_format:Y-m-d'],
            'params.gdr_cd' => ['bail', 'required', 'numeric', 'in:1,2'],
        ];
    }

    public function attributes()
    {
        return [
            'params.id_crd_no' => '身份证号',
            'params.slf_tel_no' => '手机号',
            'params.rsdnt_nm' => '姓名',
            'params.bth_dt' => '出生日期',
            'params.gdr_cd' => '性别',
        ];
    }
}
