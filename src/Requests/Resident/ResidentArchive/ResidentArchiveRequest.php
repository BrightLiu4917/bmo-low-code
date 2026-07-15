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
            'updated_at' => ['bail', 'nullable', 'string'],
            'data_source' => ['bail', 'nullable', 'integer'],
        ];
    }

    public function attributes(): array
    {
        return [
            'empi' => '居民主索引',
            'attributes' => '属性',
            'updated_at' => '更新时间',
            'data_source' => '数据来源',
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
            'updateInfo' => ['empi', 'attributes', 'updated_at', 'data_source'],
        ];
    }
}
