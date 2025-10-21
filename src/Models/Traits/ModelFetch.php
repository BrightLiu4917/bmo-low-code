<?php

declare(strict_types = 1);

namespace  BrightLiu\LowCode\Models\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * 按指定字段获取数据
 */
trait ModelFetch
{
    /**
     * 按指定字段获取数据
     *
     * @param Model|int $data
     * @param array $columns
     * @param string $indexKey
     * @param null|\Closure $builder
     *
     * @return null|static
     */
    public static function fetch(Model|int $data, array $columns = ['*'], string $indexKey = 'id', ?\Closure $builder = null): ?static
    {
        // @phpstan-ignore return.type
        return rescue(
            fn () => match (true) {
                empty($data) => null,
                // 按主键查询
                is_numeric($data) => static::query()->when(!empty($builder), $builder)->find($data, $columns),
                // 未指定查询字段或查询字段都按预期存在时原样返回
                empty($columns) || $columns[0] === '*' || empty(array_diff($columns, array_keys($data->getAttributes()))) => $data,
                // 不符合上述条件时，按主键进行查询
                default => self::fetch(intval($data?->{$indexKey} ?? 0), $columns, $indexKey),
            },
            null
        );
    }

    /**
     * @param string $filterField
     * @param string $value
     * @param array  $columns
     * @param array  $where
     *
     * @return mixed
     */
    public static function fetchDataByField (string $filterField = 'code', string $value = '', array $columns = ['*'],array $where = [])
    {
        $result = static::query()->where([$filterField=>$value])->select($columns)->when(!empty($where),fn($query) => $query->where($where))->first();
        if (!empty($result) && count($columns) == 1 && $columns[0]  !== '*'){
            return $result->{$columns[0]};
        }
        return $result;
    }
}
