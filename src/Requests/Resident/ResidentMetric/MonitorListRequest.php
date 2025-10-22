<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Requests\Resident\ResidentMetric;

use Gupo\BetterLaravel\Validation\BaseRequest;

class MonitorListRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['bail', 'required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id' => '居民主索引',
        ];
    }
}
