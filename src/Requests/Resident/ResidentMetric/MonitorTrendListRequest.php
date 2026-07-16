<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Requests\Resident\ResidentMetric;

use Gupo\BetterLaravel\Validation\BaseRequest;

class MonitorTrendListRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'empi' => ['bail', 'required', 'string'],
            'metric_id' => ['bail', 'required', 'string'],
            'date_range' => ['bail', 'nullable', 'array'],
            'date_range.0' => ['bail', 'nullable', 'date_format:Y-m-d'],
            'date_range.1' => ['bail', 'nullable', 'date_format:Y-m-d'],
            'sort' => ['bail', 'nullable', 'string', 'in:asc,desc'],
            'with_batch' => ['bail', 'nullable', 'boolean'],
            'with_warning' => ['bail', 'nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'empi' => '居民主索引',
            'metric_id' => '指标ID',
            'date_range' => '时间范围',
            'sort' => '排序方式',
            'with_batch' => '是否获取同批次指标',
            'with_warning' => '是否获取预警信息',
        ];
    }

}
