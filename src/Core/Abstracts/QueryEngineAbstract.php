<?php

namespace BrightLiu\LowCode\Core\Abstracts;

use Illuminate\Support\Arr;
use Illuminate\Database\Connection;
use BrightLiu\LowCode\Enums\HeaderEnum;
use BrightLiu\LowCode\Traits\InstanceMake;
use BrightLiu\LowCode\Enums\Foundation\Logger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Query\Builder;
use BrightLiu\LowCode\Core\DbConnectionManager;
use Illuminate\Support\Facades\DB;
use Gupo\BetterLaravel\Exceptions\ServiceException;
use BrightLiu\LowCode\Core\Traits\DynamicWhereTrait;
use BrightLiu\LowCode\Exceptions\QueryEngineException;
use BrightLiu\LowCode\Traits\Context\WithDiseaseContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use BrightLiu\LowCode\Core\Contracts\QueryEngineContract;
use BrightLiu\LowCode\Core\Traits\DynamicMultiOrderTrait;
use BrightLiu\LowCode\Services\LowCode\LowCodeDatabaseSourceService;

/**
 * 查询引擎抽象类
 *
 * 该类提供了查询引擎的基本功能，包括缓存设置、数据库连接管理、查询构建和执行等。
 */
abstract class QueryEngineAbstract implements QueryEngineContract
{
    use
        DynamicWhereTrait,
        WithDiseaseContext,
        InstanceMake,
        DynamicMultiOrderTrait;

    // 查询构建器
    protected ?Builder $queryBuilder = null;
    //设置缓存时间
    protected int $cacheTtl = 0;
    //缓存key
    protected ?string $cacheKey = null;
    //唯一编码
    protected ?string $code = '';
    //是否打印SQL
    protected ?bool $printSql = false;

    // 连接和表配置
    public string $database = '';
    public string $table = '';
    public string $databaseTable = '';

    /**
     * 自动连接数据库（双模式支持）
     *
     * @param string|null $diseaseCode
     *
     * @return $this
     * @throws ServiceException
     */
    public function autoClient(?string $diseaseCode = null): self
    {

        try {
            // 模式1：显式传参优先（定时任务）
            if ($diseaseCode !== null) {
                $this->initWithDiseaseCode($diseaseCode);
                return $this;
            }

            $contextCode = $this->getDiseaseCode() ?:
                request()->header(HeaderEnum::DISEASE_CODE);
            // 模式2：自动上下文（常规请求）
            if (!empty($contextCode)) {
                // 尝试从上下文获取疾病编码
                $this->initWithDiseaseCode($contextCode);
            }

        } catch (ServiceException $e) {

        }
        return $this;
    }

    /**
     * 使用疾病编码初始化
     */
    private function initWithDiseaseCode(string $diseaseCode): void
    {
        try {
            $sourceCode = LowCodeDatabaseSourceService::instance()
                                                      ->getDataByDiseaseCode($diseaseCode);
            $this->clientConnByCode($sourceCode);
            $this->useTable();
            $this->fillableFields();
        } catch (\Exception $e) {
            throw new ServiceException("初始化失败: ".$e->getMessage());
        }
    }

    /**
     * 根据客户端编码设置数据库连接
     *
     * @param string|null $code 客户端编码
     *
     * @return self
     * @throws ServiceException 如果编码为空
     */
    public function clientConnByCode(?string $code = null): self
    {
        if (empty($code)) {
            throw new ServiceException('客户端入参 “编码” 不能为空');
        }
        // 设置客户端编码
        $this->code = $code;

        //  获取数据库连接
        $this->connection = DbConnectionManager::getInstance()->getConnection(
            $code
        );
        //  获取数据库表
        $config         = $this->connection->getConfig();
        $this->database = data_get($config, 'database', '');
        $this->table    = data_get($config, 'table', '');
        return $this;
    }

    /**
     * @param bool $printSql
     *
     * @return $this
     */
    public function printSql(bool $printSql = true): self
    {
        $this->printSql = $printSql;
        return $this;
    }

    /**
     * @return void
     * @throws QueryEngineException
     */
    protected function confirmQueryBuilderInit(): void
    {
        if (!$this->queryBuilder) {
            if (!$this->table || !$this->database) {
                throw new QueryEngineException('请先指定数据库和表名');
            }
            $this->databaseTable = $this->database.'.'.$this->table;
            $this->queryBuilder  = $this->connection->table(
                $this->databaseTable
            );
        }
    }

    /**
     * 设置查询使用的数据库表
     *
     * @param string $table    表名
     * @param string $database 数据库名
     *
     * @return self
     */
    public function useTable(string $table = '', string $database = ''): self
    {
        $this->table         = $table ?: $this->table;
        $this->database      = $database ?: $this->database;
        $this->databaseTable = $this->database.'.'.$this->table;
        $this->queryBuilder  = $this->connection->table($this->databaseTable);
        return $this;
    }


    /**
     *
     *  生成缓存键
     *
     *  根据当前查询的SQL语句和绑定参数生成唯一的缓存键。
     *
     * @param string $randomKey
     *
     * @return string
     * @return string
     */
    protected function generateCacheKey(string $randomKey = ''): string
    {
        $sql      = $this->queryBuilder->toSql();
        $bindings = json_encode($this->queryBuilder->getBindings());
        return md5($sql.$bindings.$randomKey);
    }

    /**
     * 执行查询并处理缓存
     *
     * @param callable $callback 查询回调函数
     * @param array    $columns  查询的列
     * @param bool     $useCache 是否使用缓存
     *
     * @return mixed 查询结果
     */
    protected function executeQuery(
        callable $callback, array $columns = ['*'],
        bool $useCache = true, string $randomKey = '',
    ) {
        try {
            if ($this->printSql || request()?->input('print_sql')) {
                //打印SQL语句
                $this->queryBuilder->dd();
            }
            //二次验证是否 初始化
            $this->confirmQueryBuilderInit();
            if ($useCache && $this->cacheTtl > 0) {
                $key = $this->cacheKey ?? $this->generateCacheKey($randomKey);
                return Cache::remember(
                    $key, $this->cacheTtl, fn () => $callback($columns)
                );
            }
            return $callback($columns);
        } catch (QueryEngineException $e) {
        }
    }


    /**
     * 获取所有查询结果
     *
     * @param array $columns  查询的列
     * @param bool  $useCache 是否使用缓存
     *
     * @return mixed 查询结果
     */
    public function getAllResult(array $columns = ['*'], bool $useCache = true)
    {
        return $this->executeQuery(
            fn ($columns) => $this->queryBuilder->get($columns), $columns,
            $useCache, $this->randomKey(column: $columns, method: __FUNCTION__)
        );
    }

    /**
     * 获取单条查询结果
     *
     * @param array $columns  查询的列
     * @param bool  $useCache 是否使用缓存
     *
     * @return mixed 查询结果
     */
    public function getOnceResult(array $columns = ['*'], bool $useCache = true,
    ): mixed {
        return $this->executeQuery(
            fn ($columns) => $this->queryBuilder->first($columns), $columns,
            $useCache, $this->randomKey(column: $columns, method: __FUNCTION__)
        );
    }

    /**
     * 获取查询结果的数量
     *
     * @param bool $useCache 是否使用缓存
     *
     * @return int|string 查询结果的数量
     */
    public function getCountResult(bool $useCache = true): int|string
    {
        return (int)$this->executeQuery(fn () => $this->queryBuilder->count(),
            [], $useCache, $this->randomKey(method: __FUNCTION__));
    }

    /**
     * 获取分页查询结果
     *
     *
     * @return LengthAwarePaginator 分页结果
     * @throws QueryEngineException 如果分页查询异常
     */
    public function getPaginateResult(): LengthAwarePaginator
    {
        try {
            if ($this->printSql || request()?->input('print_sql')) {
                //打印SQL语句
                $this->queryBuilder->dd();
            }
            return $this->queryBuilder->customPaginate(true);
        } catch (\Throwable $e) {
            Log::error('分页查询异常：'.$e->getMessage(), ['exception' => $e]);
            throw new QueryEngineException('分页查询异常');
        }
    }

    /**
     * 获取单个字段的值
     *
     * @param string $column   字段名
     * @param bool   $useCache 是否使用缓存
     *
     * @return mixed 字段值
     * @throws QueryEngineException 如果查询单个字段异常
     */
    public function getValueResult(string $column = 'id', bool $useCache = true,
    ): mixed {
        try {
            return $this->executeQuery(
                fn () => $this->queryBuilder->value($column), [], $useCache,
                $this->randomKey(column: $column, method: __FUNCTION__)
            );
        } catch (\Throwable $e) {
            Logger::LOW_CODE_LIST->error('查询单个字段异常：'.$e->getMessage(), ['exception' => $e]);
            throw new QueryEngineException('查询单个字段异常');
        }
    }

    /**
     * 随机键
     *
     * @param        $column
     * @param string $method
     *
     * @return string
     */
    protected function randomKey($column = '', string $method = ''): string
    {
        if (!empty($column) && is_array($column)) {
            $column = json_encode($column);
        }

        return md5($column.$method);
    }

    /**
     * 获取单个字段的集合
     *
     * @param string $column   字段名
     * @param bool   $useCache 是否使用缓存
     *
     * @return mixed 字段集合
     * @throws QueryEngineException 如果查询单个集合异常
     */
    public function getPluckResult(string $column = 'id', bool $useCache = true,
    ): mixed {
        try {
            return $this->executeQuery(
                fn () => $this->queryBuilder->pluck($column), [], $useCache,
                $this->randomKey(column: $column, method: __FUNCTION__)
            );
        } catch (\Throwable $e) {
            Logger::LOW_CODE_LIST->error('查询单个集合异常：'.$e->getMessage(), ['exception' => $e]
            );
            throw new QueryEngineException('查询单个集合异常');
        }
    }

    /**
     * 添加原生SQL条件
     *
     * @param string $sql      SQL语句
     * @param array  $bindings 绑定参数
     * @param string $boolean  逻辑运算符（and/or）
     *
     * @return self
     */
    public function whereRaw(
        string $sql, array $bindings = [],
        string $boolean = 'and',
    ): self {
        $this->queryBuilder = $this->queryBuilder->whereRaw(
            $sql, $bindings, $boolean
        );
        return $this;
    }

    /**
     * 添加子查询条件
     *
     * @param \Closure $callback 子查询回调
     * @param string   $boolean  逻辑运算符（and/or）
     * @param bool     $not      是否取反
     *
     * @return self
     */
    public function whereSub(
        \Closure $callback, string $boolean = 'and',
        bool $not = false,
    ): self {
        $this->queryBuilder = $not ? $this->queryBuilder->whereNotExists(
            $callback, $boolean
        ) : $this->queryBuilder->whereExists($callback, $boolean);
        return $this;
    }

    /**
     * 设置查询的列
     *
     * @param array $columns 查询的列
     *
     * @return self
     */
    public function select(array $columns = ['*']): self
    {
        $this->queryBuilder = $this->queryBuilder->select($columns);
        return $this;
    }

    /**
     * @param array $values
     *
     * @return $this
     */
    public function groupBy(array $values = []): self
    {
        $this->queryBuilder = $this->queryBuilder->groupBy($values);
        return $this;
    }

    /**
     * 原生查询
     *
     * @param string $rawSql
     * @param array  $bindings
     *
     * @return array
     * @throws QueryEngineException
     */
    public function rawQuery(string $rawSql, array $bindings = []): array
    {
        try {
            // 使用当前动态连接执行 SQL 查询
            return $this->connection->select(
                query: $rawSql, bindings: $bindings
            );
        } catch (\Throwable $e) {
            throw new QueryEngineException('执行原生SQL查询异常');
        }
    }


    /**
     * @param string $rawSql
     * @param array  $bindings
     * @param bool   $useCache
     * @param string $randomKey
     *
     * @return mixed
     */
    public function getRawResult(
        string $rawSql, array $bindings = [],
        bool $useCache = true, string $randomKey = '',
    ): mixed {
        return $this->executeQuery(
            fn () => $this->connection->select($rawSql, $bindings), [],
            // 原生查询不需要传列
            $useCache, $randomKey ?: md5($rawSql.json_encode($bindings))
        );
    }

    /*
     *
     */
    public function fillableFields(): static
    {
        if (empty($this->database) || empty($this->table)) {
            throw new \InvalidArgumentException("缺少 database 或 table 属性");
        }
        try {
            $cacheKey       = "fillable_fields:{$this->database}:{$this->table}";
            $this->fillable = Cache::remember($cacheKey, 86400, function() {
                return $this->connection->table('INFORMATION_SCHEMA.COLUMNS')
                                        ->where(
                                            [
                                                'TABLE_SCHEMA' => $this->database,
                                                'TABLE_NAME'   => $this->table,
                                            ]
                                        )->pluck('COLUMN_NAME')->toArray();
            });
        } catch (\Throwable $e) {
            Logger::WIDTH_TABLE_DATA_RESIDENT->error(
                '获取 fillable 字段失败',
                [
                    'database' => $this->database, 'table' => $this->table,
                    'error'    => $e->getMessage(),
                ]
            );
            $this->fillable = []; // 防止后续使用时报错
        }

        return $this;
    }

    /**
     * @param array|null  $args
     * @param string|null $uniqueKey
     *
     * @return int
     */
    public function upsert(?array $args = null, ?string $uniqueKey = null)
    {
        try {
            $fillabes    = $this->filterFillableFields($args, $this->fillable);
            $columnsKeys = count($args) !== count($args, COUNT_RECURSIVE)
                ? array_keys(Arr::first($fillabes)) : array_keys($fillabes);
            return $this->queryBuilder->upsert(
                [$fillabes], $uniqueKey, $columnsKeys
            );
        } catch (\Exception $e) {
            Logger::WIDTH_TABLE_DATA_RESIDENT->error(
                '宽表写入失败'.$e->getMessage(),
            );
        }
    }

    /**
     * 追加字段
     *
     * @param            $array
     * @param array|null $fillableFields
     *
     * @return array
     */
    protected function appendDefaultData(
        $array = [],
        ?array $fillableFields = [],
    ): array {
        $nowTime = now()->format('Y-m-d H:i:s');
        $array   = Arr::only($array, $fillableFields);
        $array   = array_filter($array, function($value) {
            return $value !== '';
        }, ARRAY_FILTER_USE_BOTH);

        if (isset($array['id_crd_no'])) {
            $array['user_id'] = md5($array['id_crd_no']);
            $array['crt_tm']  = $nowTime;
        }
        return $array;
    }

    /**
     * @param array|null $attributes
     * @param array|null $fillable
     *
     * @return array
     */
    protected function filterFillableFields(
        ?array $attributes = [],
        ?array $fillable = [],
    ): array {
        // 检查属性数组是否为二维数组
        $isTwoDimensional = count($attributes) !== count(
                $attributes, COUNT_RECURSIVE
            );

        if ($isTwoDimensional) {
            //如果每个子数组是二维数组，则对其进行处理
            foreach ($attributes as $key => $array) {
                $attributes[$key] = $this->appendDefaultData($array, $fillable);
            }
        } else {
            //如果是一维数组，则正常处理
            $attributes = $this->appendDefaultData($attributes, $fillable);
        }
        return $attributes;
    }

    public function insertData(?array $args = [])
    {
        try {
            $fillabes = $this->filterFillableFields($args, $this->fillable);
            return $this->queryBuilder->insert($fillabes);
        } catch (\Exception $e) {
            Logger::WIDTH_TABLE_DATA_RESIDENT->error(
                '宽表写入失败'.$e->getMessage(),
            );
        }
    }


    /**
     * @param array|null $args
     *
     * @return int
     */
    public function update(?array $args = [])
    {
        try {
            $fillabes = $this->filterFillableFields($args, $this->fillable);
            //销毁唯一主键不做更新条件 宽表会报错
            unset($fillabes['user_id'], $fillabes['id_crd_no']);
            unset($args['user_id'], $args['id_crd_no']);
            return $this->queryBuilder->update($fillabes);
        } catch (\Exception $e) {
            Logger::WIDTH_TABLE_DATA_RESIDENT->error(
                '宽表更新失败'.$e->getMessage(),
            );
        }
    }

    /**
     * @param int         $ttl
     * @param string|null $cacheKey
     *
     * @return $this
     */
    public function setCache(int $ttl, ?string $cacheKey = null): self
    {
        $this->cacheTtl = max(0, $ttl);
        $this->cacheKey = $cacheKey;
        return $this;
    }

    public function rawTable(string $table)
    {
        $query = clone $this;

        $query->setQueryBuilder(
            $query->getConnection()->table(DB::raw($table))
        );

        return $query;
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function setQueryBuilder(Builder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    public function getQueryBuilder(): Builder
    {
        return $this->queryBuilder;
    }
}
