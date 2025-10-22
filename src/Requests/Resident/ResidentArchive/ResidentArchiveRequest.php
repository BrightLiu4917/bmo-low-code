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
            'user_id' => ['bail', 'nullable', 'required'],
            'attributes' => ['bail', 'required', 'array'],
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id' => '居民主索引',
            'attributes' => '属性',
        ];
    }

    public function scenes(): array
    {
        return [
            'basicInfo' => ['user_id'],
            'follow' => ['user_id'],
            'unfollow' => ['user_id'],
            'maskTesting' => ['user_id'],
            'unmaskTesting' => ['user_id'],
            'info' => ['user_id'],
            'updateInfo' => ['user_id', 'attributes'],
        ];
    }
}
