<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Requests\Resident\ResidentManagement;

use Gupo\BetterLaravel\Validation\BaseRequest;

final class PreRequest extends BaseRequest
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
