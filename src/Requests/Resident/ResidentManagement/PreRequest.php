<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Requests\Resident\ResidentManagement;

use Gupo\BetterLaravel\Validation\BaseRequest;

final class PreRequest extends BaseRequest
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
