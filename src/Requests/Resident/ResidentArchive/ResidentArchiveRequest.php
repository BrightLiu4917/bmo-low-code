<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Requests\Resident\ResidentArchive;

use Gupo\BetterLaravel\Validation\BaseRequest;
use Gupo\BetterLaravel\Validation\Traits\ValidatorScenes;

class ResidentArchiveRequest extends BaseRequest
{
    use ValidatorScenes;

    public function rules(): array
    {
        return [
            'empi' => ['bail', 'nullable', 'required'],
            'attributes' => ['bail', 'required', 'array'],
        ];
    }

    public function attributes(): array
    {
        return [
            'empi' => '居民主索引',
            'attributes' => '属性',
        ];
    }

    public function scenes(): array
    {
        return [
            'basicInfo' => ['empi'],
            'follow' => ['empi'],
            'unfollow' => ['empi'],
            'maskTesting' => ['empi'],
            'unmaskTesting' => ['empi'],
            'info' => ['empi'],
            'updateInfo' => ['empi', 'attributes'],
        ];
    }
}
