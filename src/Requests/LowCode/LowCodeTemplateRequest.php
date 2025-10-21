<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Requests\LowCode;

use Gupo\BetterLaravel\Validation\BaseRequest;
use Gupo\BetterLaravel\Validation\Traits\ValidatorScenes;

/**
 * 低代码-模板
 *
 */
final class LowCodeTemplateRequest extends BaseRequest
{
    use ValidatorScenes;

    /**
     * @return array
     */
    public function rules(): array
    {

        return [
            'id'   => [
                'bail',
                'required',
                'numeric',
            ],
            'name' => [
                'bail',
                'required',
                'string',
                'max:32',
            ],

            'template_type' => [
                'bail',
                'numeric',
                'required',
                'in:1,2,4,3,5',
            ],

            'description' => [
                'bail',
                'max:128',
            ],

            'content_type' => [
                'bail',
                'required',
                'numeric',
                'in:1,2,3,4,5',
            ],

            'weight' => [
                'bail',
                'numeric',
            ],
        ];
    }

    /**
     * @return array
     */
    public function attributes(): array
    {
        return [
            'id'            => 'ID', 'name' => '名称',
            'template_type' => '模板类型', 'content_type' => '内容类型',
            'description'   => '描述', 'password' => '密码',
            'weight'        => '权重(排序)', 'content' => '内容',
        ];
    }

    /**
     * @return array
     */
    public function scenes(): array
    {
        return [
            'create' => [
                'name', 'template_type', 'content_type',
                'description', 'template_type', 'weight',
            ],

            'update' => [
                'name', 'template_type', 'content_type',

                'description', 'template_type', 'weight', 'id',
            ],

            'delete' => ['id',], 'show' => ['id',],
        ];
    }
}
