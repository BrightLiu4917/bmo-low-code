<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Requests\LowCode;

use Gupo\BetterLaravel\Validation\BaseRequest;
use Gupo\BetterLaravel\Validation\Traits\ValidatorScenes;

/**
 * 低代码-零件
 */
final class LowCodePartRequest extends BaseRequest
{
    use ValidatorScenes;

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'id'           => [
                'bail',
                'required',
                'numeric',
            ],
            'name'         => [
                'bail',
                'required',
                'string',
                'max:32',
            ],
            'part_type'    => [
                'bail',
                'numeric',
                'required',
                'in:1,2',
            ],
            'description'  => [
                'bail',
                'max:128',
            ],
            'content_type' => [
                'bail',
                'required',
                'required',
                'in:1,2,3,4,5',
            ],
            'content'      => [
                'bail',
                'array',
                'nullable',
            ],
            'weight'       => [
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
            'id'          => 'ID', 'name' => '名称',
            'part_type'   => '零件类型', 'content_type' => '内容类型',
            'description' => '描述', 'weight' => '权重(排序)',
            'content'     => '内容',
        ];
    }

    /**
     * @return array
     */
    public function scenes(): array
    {
        return [
            'create' => [
                'name', 'part_type', 'content_type', 'content',
                'description', 'weight',
            ],
            'update' => [
                'name', 'content_type', 'content',
                'description', 'part_type', 'weight', 'id',
            ],
            'delete' => ['id',], 'show' => ['id',],
        ];
    }
}
