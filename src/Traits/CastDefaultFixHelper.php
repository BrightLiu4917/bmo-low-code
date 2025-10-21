<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Traits;

trait CastDefaultFixHelper
{
    /**
     * 根据指定的类型转换规则，修正输入数据。
     *
     * 该函数遍历给定的类型转换规则，对输入数据中的每个字段进行类型转换。
     * 如果字段在输入数据中不存在，则跳过该字段的处理。
     * 支持的转换类型包括：string、integer/int、boolean/bool、array、json、float/double、datetime/timestamp。
     * 对于未知类型，保持原值不变。
     *
     * @param array $data  输入数据，键值对形式。
     * @param array $casts 类型转换规则，键为字段名，值为目标类型。
     *
     * @return array 修正后的数据，键值对形式。
     */
    public function fixInputDataByCasts($data = null, $casts = null): array
    {
        //预处理 这个有可能入参是一个 model 路径
        if (!is_array($casts)) {
            $casts = (new $casts())->getCasts();
        }
        //这里有可能是一个 集合
        if (!is_array($data)) {
            $data = $data->toArray();
        }

        foreach ($casts as $field => $type) {
            // 如果字段在输入数据中不存在，则跳过处理
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];//具体内容

            // 根据类型进行相应的转换
            $data[$field] = match ($type) {
                'string'                => is_string($value) ? $value : '',
                'integer', 'int'        => is_numeric($value) ? (int)$value : 0,
                'boolean', 'bool'       => is_bool($value) ? $value : false,
                'array'                 => is_array($value) ? $value :
                    null,
                'float', 'double'       => is_numeric($value) ? (float)$value :
                    0.0,
                'datetime', 'timestamp' => (is_string($value) &&
                    strtotime($value) !== false) ? $value : null,
                default                 => $value, // 处理未知类型，保持原值不变
            };
        }
        return $data;
    }
}
