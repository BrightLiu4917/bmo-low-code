<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Requests\Foundation\PersonalizeMenu;

use Gupo\BetterLaravel\Validation\BaseRequest;

final class SaveRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'items' => ['bail', 'array'],
            'items.*.title' => ['bail', 'required', 'string', 'min:1', 'max:15'],
            'items.*.module_type' => ['bail', 'nullable', 'string'],
            'items.*.module_id' => ['bail', 'required', 'string'],
            'items.*.metadata' => ['bail', 'nullable', 'array'],
        ];
    }

    public function attributes(): array
    {
        return [
            'items' => '菜单项',
            'items.*.title' => '菜单项标题',
            'items.*.module_type' => '菜单项模块类型',
            'items.*.module_id' => '菜单项模块ID',
            'items.*.metadata' => '菜单项元数据',
        ];
    }
}
