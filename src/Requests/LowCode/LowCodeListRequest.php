<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Requests\LowCode;

use Illuminate\Validation\Rule;
use Gupo\BetterLaravel\Validation\BaseRequest;
use App\Http\V1\Requests\Contracts\Rule\NullOrArrayRule;
use Gupo\BetterLaravel\Validation\Traits\ValidatorScenes;

/**
 * 低代码 - 列表请求验证
 */
final class LowCodeListRequest extends BaseRequest
{
    use ValidatorScenes;

    /**
     * 所有字段校验规则
     */
    public function rules(): array
    {
        return ['id'                                              => ['bail',
                                                                      'required',
                                                                      'numeric'],
                "admin_name"                                      => ['bail',
                                                                      'required',
                                                                      'string',
                                                                      'max:64'],
                'parent_code'                                     => ['bail',
                                                                      'max:64'],
                'family_doctor_name'                              => ['bail',
                                                                      'max:64'],
                'mobile_doctor_name'                              => ['bail',
                                                                      'max:64'],
                'admin_weight'                                    => ['bail',
                                                                      'required',
                                                                      'min:0'],
                'family_doctor_weight'                            => ['bail',
                                                                      'numeric',
                                                                      'min:0'],
                'mobile_doctor_weight'                            => ['bail',
                                                                      'numeric',
                                                                      'min:0'],
                'crowd_type_code'                                 => ['bail',
                                                                      'required',
                                                                      'string',
                                                                      'max:64',
                                                                      Rule::exists(
                                                                          'crowd_types',
                                                                          'code'
                                                                      )->where(
                                                                          function($query,
                                                                          ) {
                                                                              $query->whereNull(
                                                                                  'deleted_at'
                                                                              );
                                                                          }
                                                                      ),],

                'route_group'=>[
                    new NullOrArrayRule(),
                    'required',
                ],
                'append_field_json'                               => ['bail',
                                                                      new NullOrArrayRule(),
                                                                      function($attribute,
                                                                          $value,
                                                                          $fail,
                                                                      ) {
                                                                          if (!empty($value)) {
                                                                              if (count(
                                                                                      array_filter(
                                                                                          $value,
                                                                                          'is_array'
                                                                                      )
                                                                                  )
                                                                                  !== count(
                                                                                      $value
                                                                                  )
                                                                              ) {
                                                                                  return $fail(
                                                                                      "字段 {$attribute} 必须是二维数组（每一项都是数组）"
                                                                                  );
                                                                              }
                                                                          }
                                                                      }],

                'append_column_json' => ['bail', 'nullable', 'array',
                                         function($attribute, $value, $fail) {
                                             if (!empty($value)) {
                                                 if (count(
                                                         array_filter(
                                                             $value, 'is_array'
                                                         )
                                                     ) !== count($value)
                                                 ) {
                                                     return $fail(
                                                         "字段 {$attribute} 必须是二维数组（每一项都是数组）"
                                                     );
                                                 }
                                             }
                                         }],

                'append_filter_json' => ['bail', 'nullable', 'array',
                                         function($attribute, $value, $fail) {
                                             if (!empty($value)) {
                                                 if (count(
                                                         array_filter(
                                                             $value, 'is_array'
                                                         )
                                                     ) !== count($value)
                                                 ) {
                                                     return $fail(
                                                         "字段 {$attribute} 必须是二维数组（每一项都是数组）"
                                                     );
                                                 }
                                             }
                                         }],

                'append_button_json' => ['bail', 'nullable', 'array',
                                         function($attribute, $value, $fail) {
                                             if (!empty($value)) {
                                                 if (count(
                                                         array_filter(
                                                             $value, 'is_array'
                                                         )
                                                     ) !== count($value)
                                                 ) {
                                                     return $fail(
                                                         "字段 {$attribute} 必须是二维数组（每一项都是数组）"
                                                     );
                                                 }
                                             }
                                         }],

                'append_top_button_json' => ['bail', 'nullable', 'array',
                                             function($attribute, $value, $fail,
                                             ) {
                                                 if (!empty($value)) {
                                                     if (count(
                                                             array_filter(
                                                                 $value,
                                                                 'is_array'
                                                             )
                                                         ) !== count($value)
                                                     ) {
                                                         return $fail(
                                                             "字段 {$attribute} 必须是二维数组（每一项都是数组）"
                                                         );
                                                     }
                                                 }
                                             }],

                'remove_field_json' => ['bail', 'nullable', 'array',
                                        function($attribute, $value, $fail) {
                                            if (!empty($value)) {
                                                if (count(
                                                        array_filter(
                                                            $value, 'is_array'
                                                        )
                                                    ) !== count($value)
                                                ) {
                                                    return $fail(
                                                        "字段 {$attribute} 必须是二维数组（每一项都是数组）"
                                                    );
                                                }
                                            }
                                        }],

                'remove_filter_json' => ['bail', 'nullable', 'array',
                                         function($attribute, $value, $fail) {
                                             if (!empty($value)) {
                                                 if (count(
                                                         array_filter(
                                                             $value, 'is_array'
                                                         )
                                                     ) !== count($value)
                                                 ) {
                                                     return $fail(
                                                         "字段 {$attribute} 必须是二维数组（每一项都是数组）"
                                                     );
                                                 }
                                             }
                                         }],

                'remove_column_json' => ['bail', 'nullable', 'array',
                                         function($attribute, $value, $fail) {
                                             if (!empty($value)) {
                                                 if (count(
                                                         array_filter(
                                                             $value, 'is_array'
                                                         )
                                                     ) !== count($value)
                                                 ) {
                                                     return $fail(
                                                         "字段 {$attribute} 必须是二维数组（每一项都是数组）"
                                                     );
                                                 }
                                             }
                                         }],

                'remove_button_json' => ['bail', 'nullable', 'array',
                                         function($attribute, $value, $fail) {
                                             if (!empty($value)) {
                                                 if (count(
                                                         array_filter(
                                                             $value, 'is_array'
                                                         )
                                                     ) !== count($value)
                                                 ) {
                                                     return $fail(
                                                         "字段 {$attribute} 必须是二维数组（每一项都是数组）"
                                                     );
                                                 }
                                             }
                                         }],

                'remove_top_button_json'   => ['bail', 'nullable', 'array',
                                               function($attribute, $value,
                                                   $fail,
                                               ) {
                                                   if (!empty($value)) {
                                                       if (count(
                                                               array_filter(
                                                                   $value,
                                                                   'is_array'
                                                               )
                                                           ) !== count($value)
                                                       ) {
                                                           return $fail(
                                                               "字段 {$attribute} 必须是二维数组（每一项都是数组）"
                                                           );
                                                       }
                                                   }
                                               }], //
                'template_code_filter'     => ['bail', 'nullable', 'string', 'max:64'],
                'template_code_field'      => ['bail', 'nullable', 'string', 'max:64'],
                'template_code_column'     => ['bail', 'required', 'string', 'max:64'],
                'template_code_button'     => ['bail', 'nullable', 'string', 'max:64'],
                'template_code_top_button' => ['bail', 'nullable', 'string', 'max:64'],];
    }

    /**
     * 字段别名
     */
    public function attributes(): array
    {
        return ['id'                       => 'ID',
                'parent_code'              => '上级列表编码',
                'admin_name'               => '专病后台列表名称',
                'family_doctor_name'       => '家庭医生列表名称',
                'mobile_doctor_name'       => '移动医生列表名称',
                'admin_weight'             => '专病后台权重',
                'family_doctor_weight'     => '家庭医生权重',
                'mobile_doctor_weight'     => '移动医生权重',
                'crowd_type_code'          => '人群类型编码',
                'append_field_json'        => '追加字段',
                'append_column_json'       => '追加表头',
                'append_filter_json'       => '追加筛选条件',
                'append_button_json'       => '追加按钮',
                'append_top_button_json'   => '追加顶部按钮',
                'remove_field_json'        => '移除字段',
                'remove_filter_json'       => '移除筛选条件',
                'remove_column_json'       => '移除表头',
                'remove_button_json'       => '移除按钮',
                'remove_top_button_json'   => '移除顶部按钮',
                'template_code_filter'     => '模板-筛选',
                'template_code_field'      => '模板-字段',
                'template_code_column'     => '模板-表头',
                'template_code_button'     => '模板-按钮',
                'route_group'               =>'路由组',
                'template_code_top_button' => '模板-顶部按钮',];
    }

    /**
     * 场景定义
     */
    public function scenes(): array
    {
        return ['create' => ["admin_name", 'parent_code', 'admin_name',
                             'family_doctor_name', 'mobile_doctor_name',
                             'route_group',
                             'admin_weight', 'family_doctor_weight',
                             'mobile_doctor_weight', 'crowd_type_code',
                             'append_field_json', 'append_column_json',
                             'append_filter_json', 'append_button_json',
                             'append_top_button_json', 'remove_field_json',
                             'remove_filter_json', 'remove_column_json',
                             'remove_button_json', 'remove_top_button_json',
                             'template_code_filter', 'template_code_field',
                             'template_code_column', 'template_code_button',
                             'template_code_top_button',],
                'update' => ['id', "admin_name", 'parent_code', 'admin_name',
                             'family_doctor_name', 'mobile_doctor_name',
                             'admin_weight', 'family_doctor_weight',
                             'route_group',
                             'mobile_doctor_weight', 'crowd_type_code',
                             'append_field_json', 'append_column_json',
                             'append_filter_json', 'append_button_json',
                             'append_top_button_json', 'remove_field_json',
                             'remove_filter_json', 'remove_column_json',
                             'remove_button_json', 'remove_top_button_json',
                             'template_code_filter', 'template_code_field',
                             'template_code_column', 'template_code_button',
                             'template_code_top_button',], 'delete' => ['id'],
                'show' => ['id'],];
    }
}
