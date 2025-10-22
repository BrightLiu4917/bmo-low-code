<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Requests\Resident\ResidentMetric;

use Gupo\BetterLaravel\Validation\BaseRequest;

class SaveMonitorRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['bail', 'required', 'string'],
            'metric_ids' => ['bail', 'nullable', 'array'],
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id' => '居民主索引',
            'metric_ids' => '指标ID',
        ];
    }
}
