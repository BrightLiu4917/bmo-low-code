<?php

namespace  BrightLiu\LowCode\Core\Contracts;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface QueryEngineContract
 */
interface QueryEngineContract
{
    /**
     * @param int         $ttl
     * @param string|null $cacheKey
     *
     * @return self
     */
    public function setCache(int $ttl, ?string $cacheKey = null): self;

    /**
     * @param array  $conditions
     * @param string $defaultBoolean
     *
     * @return self
     */
    public function whereMixed(array $conditions,
        string $defaultBoolean = 'and'): self;

    /**
     * @param string|array $order
     * @param string       $direction
     *
     * @return self
     */
    public function multiOrderBy(string|array $order, string $direction = 'asc'): self;

    /**
     * @param array $columns
     *
     * @return mixed
     */
    public function getAllResult(array $columns = ['*']);

    /**
     * @param array $columns
     *
     * @return mixed
     */
    public function getOnceResult(array $columns = ['*']): mixed;

    /**
     * @return int|string
     */
    public function getCountResult(): int|string;

    /**
     * @return LengthAwarePaginator
     */
    public function getPaginateResult(): LengthAwarePaginator;

    /**
     * @param string $column
     *
     * @return mixed
     */
    public function getValueResult(string $column = 'id'): mixed;

    /**
     * 获取字段集合
     *
     * @param string $column
     * @return Collection|null
     */
    public function getPluckResult(string $column = 'id'): mixed;

    /**
     * @param string $sql
     * @param array  $bindings
     * @param string $boolean
     *
     * @return self
     */
    public function whereRaw(string $sql, array $bindings = [], string $boolean = 'and'): self;

    /**
     * @param \Closure $callback
     * @param string   $boolean
     * @param bool     $not
     *
     * @return self
     */
    public function whereSub(\Closure $callback, string $boolean = 'and', bool $not = false): self;

    /**
     * @param string $value
     * @param string $primaryKey
     *
     * @return self
     */
    public function wherePrimaryKey(string $value = '', string $primaryKey = 'user_id'): self;

    /**
     * @param array $columns
     *
     * @return self
     */
    public function select(array $columns = ['*']): self;

    /**
     * @param string $value
     *
     * @return self
     */
    public function whereUserId(string $value = ''): self;

    /**
     * @param string $value
     *
     * @return self
     */
    public function whereIdCrdNo(string $value = ''): self;


    /**
     * @param array $values
     *
     * @return self
     */
    public function groupBy(array $values = []):self;

}
