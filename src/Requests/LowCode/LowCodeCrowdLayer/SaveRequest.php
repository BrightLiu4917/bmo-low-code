<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Requests\LowCode\LowCodeCrowdLayer;

use Gupo\BetterLaravel\Validation\BaseRequest;

final class SaveRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'module_id' => ['bail', 'required'],
            'items' => ['bail', 'array'],
            'items.*.title' => ['bail', 'required', 'string', 'min:1', 'max:15'],
            'items.*.crowd_id' => ['bail', 'required'],
        ];
    }

    public function attributes(): array
    {
        return [
            'module_id' => '模块ID',
            'items' => '分层项',
            'items.*.title' => '标题',
            'items.*.crowd_id' => '人群ID',
        ];
    }
}
