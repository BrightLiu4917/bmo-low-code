<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Requests\Resident\ResidentMetric;

use Gupo\BetterLaravel\Validation\BaseRequest;

class MonitorTrendItemsRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['bail', 'required', 'string'],
            'date_range' => ['bail', 'nullable', 'array'],
            'date_range.0' => ['bail', 'nullable', 'date_format:Y-m-d'],
            'date_range.1' => ['bail', 'nullable', 'date_format:Y-m-d'],
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id' => '居民主索引',
            'metric_id' => '指标ID',
            'date_range' => '时间范围',
        ];
    }
}
