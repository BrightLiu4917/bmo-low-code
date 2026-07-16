<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Requests\Resident\ResidentMetric;

use Gupo\BetterLaravel\Validation\BaseRequest;

class MonitorTrendDetailsRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'id' => ['bail', 'required', 'integer'],
            'with_batch' => ['bail', 'nullable', 'boolean'],
            'with_warning' => ['bail', 'nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => '指标记录ID',
            'with_batch' => '是否获取同批次指标',
            'with_warning' => '是否获取预警信息',
        ];
    }

}
