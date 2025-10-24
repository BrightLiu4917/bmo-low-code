<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Requests\Resident\ResidentMetric;

use Gupo\BetterLaravel\Validation\BaseRequest;

class MonitorListRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'empi' => ['bail', 'required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'empi' => '居民主索引',
        ];
    }
}
