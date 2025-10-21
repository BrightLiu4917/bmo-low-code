<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Requests\Disease;

use Illuminate\Validation\Rule;
use Gupo\BetterLaravel\Validation\BaseRequest;
use Gupo\BetterLaravel\Validation\Traits\ValidatorScenes;

/**
 * 疾病
 */
final class DiseaseRequest extends BaseRequest
{
    use ValidatorScenes;

    /**
     * @return array
     */
    public function rules(): array
    {
        $id = $this->input('id', 0);
        return [
            'id'                 => ['bail', 'required', 'numeric'],
            'code'               => [
                'bail', 'required', 'string', 'max:40',
                Rule::unique('diseases')->ignore(
                    $id
                )->whereNull(
                    'deleted_at'
                ),
            ],
            'name'               => [
                'bail', 'required', 'string', 'max:64',
                Rule::unique('diseases')->ignore(
                    $id
                )->whereNull(
                    'deleted_at'
                ),
            ],
            'extraction_pattern' => ['bail', 'string', 'max:255'],
            'weight'             => ['bail', 'numeric', 'required'],
        ];
    }

    /**
     * @return array
     */
    public function attributes(): array
    {
        return [
            'id'                 => '主键', 'code' => '疾病编码',
            'name'               => '疾病名称',
            'extraction_pattern' => '提取正则',
        ];
    }

    /**
     * @return array
     */
    public function scenes(): array
    {
        return [
            'create'    => [
                'code', 'name',
                'extraction_pattern',
                'weight',
            ],
            'update'    => [
                'id', 'code', 'name', 'extraction_pattern',
                'weight',
            ], 'delete' => ['id',],
            'show'      => ['id',],
        ];
    }
}
