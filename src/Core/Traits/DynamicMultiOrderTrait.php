<?php

namespace BrightLiu\LowCode\Core\Traits;

use Closure;
use Illuminate\Support\Arr;

/**
 * 多字段排序
 */
trait DynamicMultiOrderTrait
{
    /**
     * @param string|array $order 字段
     * @param string       $direction 排序方向
     * @param string       $rawSql 原生SQL
     *
     * @return DynamicMultiOrderTrait|\App\Support\LowCode\Core\Abstracts\QueryEngineAbstract
     */
    public function multiOrderBy(string|array $order,
        string $direction = 'desc',string $rawSql = ''
    ): self {

        //原生SQL 优先级最高 例如：CASE WHEN name = '全部人群' THEN 0  ELSE 1 END asc(desc)
        if (!empty($rawSql)){
            $this->queryBuilder->orderByRaw($rawSql);
        }


        if (empty($order)) {
            return $this;
        }
        match (true) {
            // 单字段排序
            is_string($order) => $this->queryBuilder
                = $this->queryBuilder->orderBy($order, $direction),

            // 简写：['字段', '方向']
            is_array($order) && $this->isSimplePair($order) =>
            $this->queryBuilder
                = $this->queryBuilder->orderBy(Arr::first($order),
                Arr::last($order)),

            // 多字段：[['字段1', 'asc'], ['字段2', 'desc']]
            $this->isMultiArray($order) => collect($order)->each(fn ($item) =>
            $this->queryBuilder
                = $this->queryBuilder->orderBy(Arr::first($item),
                Arr::last($item))),

            // 多字段：['字段1' => 'asc', '字段2' => 'desc']
            $this->isAssocArray($order) => $this->applyAssocOrder($order),

            default => throw new \InvalidArgumentException('无效的排序参数'),
        };

        return $this;
    }

    /**
     * 多维数组
     *
     * @param array $array
     *
     * @return bool
     */
    protected function isMultiArray(array $array): bool
    {
        return count($array) !== count($array, COUNT_RECURSIVE);
    }

    /** 关联数组
     *
     * @param array $array
     *
     * @return bool
     */
    protected function isAssocArray(array $array): bool
    {
        return Arr::isAssoc($array);
    }

    /**
     * 判断是否是 ['字段', '方向'] 简写形式
     */
    protected function isSimplePair(array $array): bool
    {
        return count($array) === 2 && is_string(Arr::first($array)) &&
            is_string($array[1]);
    }

    /** 开始排序
     *
     * @param array $order
     *
     * @return void
     */
    protected function applyAssocOrder(array $order): void
    {
        foreach ($order as $column => $dir) {
            $this->queryBuilder = $this->queryBuilder->orderBy($column, $dir);
        }
    }
}
