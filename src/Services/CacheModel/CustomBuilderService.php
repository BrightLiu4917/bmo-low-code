<?php

namespace BrightLiu\LowCode\Services\CacheModel;

use Illuminate\Database\Eloquent\Builder;
use BrightLiu\LowCode\Models\Traits\Cacheable\CacheableModel;

/**
 * CustomBuilder 类扩展了 Laravel 的 Eloquent Builder，提供了缓存功能。
 * 该类通过缓存查询结果来优化数据库查询性能。
 */
class CustomBuilderService extends Builder
{
    /**
     * 根据主键查找单个模型实例。
     *
     * @param int|string $id      要查找的模型主键值
     * @param array      $columns 要查询的列，默认为所有列
     *
     * @return mixed 返回查找到的模型实例，如果未找到则返回 null
     */
    public function find($id, $columns = ['*'])
    {
        // 如果模型不可缓存或未启用 ID 缓存，则直接调用父类方法
        if (!$this->isCacheableModel() ||
            !config('low-code.cache-model.enable-id-code-cache')) {
            return parent::find($id, $columns);
        }

        // 生成缓存键并缓存查询结果
        $key = $this->model::class.":model:id:$id";
        return CacheModelService::remember($key, $this->model::getTag(),
            fn () => parent::find($id, $columns));
    }

    /**
     * 获取查询结果中的第一个模型实例。
     *
     * @param array $columns 要查询的列，默认为所有列
     *
     * @return mixed 返回查找到的第一个模型实例，如果未找到则返回 null
     */
    public function first($columns = ['*'])
    {

        try {// 如果模型不可缓存，则直接调用父类方法
            if (!$this->isCacheableModel()) {
                return parent::first($columns);
            }                                                 // 尝试匹配 ID 或 CODE 的缓存键
            $key = $this->matchIdOrCodeFirstKey(
                $columns
            );                                                // 如果匹配到 ID 或 CODE 且启用了 ID 缓存，则缓存查询结果
            if ($key && config('low-code.cache-model.enable-id-code-cache')) {
                return CacheModelService::remember(
                    $key, $this->model::getTag(),
                    fn () => parent::first($columns)
                );
            }
            // 如果未启用查询缓存，则直接调用父类方法
            if (!config('low-code.cache-model.enable-query-cache')) {
                return parent::first($columns);
            }// 生成 SQL 缓存键并缓存查询结果
            $key = $this->generateSqlCacheKey('first', $columns);
            return CacheModelService::remember(
                $key, $this->model::getTag(), fn () => parent::first($columns)
            );
        } catch (\Exception $e) {
        }
    }

    /**
     * 获取查询结果中的所有模型实例。
     *
     * @param array $columns 要查询的列，默认为所有列
     *
     * @return mixed 返回查找到的模型实例集合
     */
    public function get($columns = ['*'])
    {
        // 如果模型不可缓存或未启用查询缓存，则直接调用父类方法
        if (!$this->isCacheableModel() ||
            !config('low-code.cache-model.enable-query-cache')) {
            return parent::get($columns);
        }

        // 生成 SQL 缓存键并缓存查询结果
        $key = $this->generateSqlCacheKey('get', $columns);
        return CacheModelService::remember($key, $this->model::getTag(),
            fn () => parent::get($columns));
    }

    /**
     * 根据查询条件匹配 ID 或 CODE 的缓存键。
     *
     * @param array $columns 要查询的列
     *
     * @return string|null 返回生成的缓存键，如果未匹配到则返回 null
     */
    protected function matchIdOrCodeFirstKey($columns)
    {
        $query = $this->getQuery(); // 拿到底层的 Query\Builder

        // 如果只有一个查询条件，则尝试匹配 ID 或 CODE
        if (count($query->wheres ?? []) === 1) {
            $where = $query->wheres[0] ?? [];

            if (
                isset($where['column'], $where['operator'], $where['value']) &&
                $where['operator'] === '='
            ) {
                if ($where['column'] === 'id') {
                    return get_class($this->model).":model:id:".$where['value'];
                }

                if ($where['column'] === 'code') {
                    return get_class($this->model).":model:code:".
                        $where['value'];
                }
            }
        }

        return null;
    }

    /**
     * 检查当前模型是否可缓存。
     *
     * @return bool 如果模型可缓存则返回 true，否则返回 false
     */
    protected function isCacheableModel(): bool
    {
        return in_array(CacheableModel::class,
            class_uses_recursive($this->model));
    }

    /**
     * 生成 SQL 查询的缓存键。
     *
     * @param string $action  查询操作类型（如 'first', 'get'）
     * @param array  $columns 要查询的列
     *
     * @return string 返回生成的缓存键
     */
    protected function generateSqlCacheKey(
        string $action, array $columns = ['*'],
    ): string {
        // 获取 SQL 查询语句、绑定参数、预加载关系等信息，并生成唯一的缓存键
        $sql      = $this->toSql();
        $bindings = json_encode($this->getBindings());
        $with     = json_encode($this->getEagerLoads());
        $model    = get_class($this->model);
        return "{$model}:query:{$action}:".
            md5($sql.$bindings.$with.implode(',', $columns));
    }
}
