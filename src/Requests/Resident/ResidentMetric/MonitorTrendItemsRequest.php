<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Requests\Resident\ResidentMetric;

use Gupo\BetterLaravel\Validation\BaseRequest;

class MonitorTrendItemsRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'empi' => ['bail', 'required', 'string'],
            'date_range' => ['bail', 'nullable', 'array'],
            'date_range.0' => ['bail', 'nullable', 'date_format:Y-m-d'],
            'date_range.1' => ['bail', 'nullable', 'date_format:Y-m-d'],
            'limit' => ['bail', 'nullable', 'numeric'],
        ];
    }

    public function attributes(): array
    {
        return [
            'empi' => '居民主索引',
            'metric_id' => '指标ID',
            'date_range' => '时间范围',
            'limit' => '条数',
        ];
    }
}
