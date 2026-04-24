<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services;

use BrightLiu\LowCode\Enums\Foundation\Logger;
use BrightLiu\LowCode\Exceptions\QueryEngineException;
use BrightLiu\LowCode\Services\LowCode\ColumnAppender\AppenderManager;
use BrightLiu\LowCode\Services\LowCode\LowCodeListService;
use BrightLiu\LowCode\Services\LowCode\Tools\EmpiFullFilterTools;
use BrightLiu\LowCode\Support\Foundation\LowCodeCustomPaginator;
use Gupo\BetterLaravel\Database\CustomLengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator as IPaginator;
use Illuminate\Database\Query\Expression;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 自定义基线表查询引擎
 * PS：用于实现优化的分页查询
 */
class CustomQueryEngineService extends QueryEngineService
{
    protected array $customQueryOptions = [
        'query_engine' => null,
        'query_params' => null,
        'config' => null,
        'biz_scene_table' => null,
    ];

    public function setQueryOptions($queryEngine, $value, $config, $bizSceneTable): void
    {
        $this->customQueryOptions = [
            'query_engine' => $queryEngine,
            'query_params' => $value,
            'config' => $config,
            'biz_scene_table' => $bizSceneTable,
        ];
    }

    /**
     * 获取分页查询结果
     * PS：重写父类方法，使用自定义分页逻辑。拆解分页列表查询、分页count查询，分别做优化。
     *
     * @param bool $isSimplePaginate 是否使用简单分页
     * @param array $columns 查询列，默认查询宽表和场景表所有列（t1.*, t2.*）
     *
     * @return IPaginator 分页结果
     *
     * @throws QueryEngineException 如果分页查询异常
     */
    public function getPaginateResult(bool $isSimplePaginate = false, array $columns = []): IPaginator
    {
        try {
            if ($this->printSql || request()?->input('print_sql')) {
                // 打印SQL语句
                $this->queryBuilder->dd();
            }

            $queryEngine = $this->customQueryOptions['query_engine'];
            $queryParams = $this->customQueryOptions['query_params'];
            $config = $this->customQueryOptions['config'];
            $bizSceneTable = $this->customQueryOptions['biz_scene_table'];

            $listSrv = LowCodeListService::make();

            $paginator = CustomLengthAwarePaginator::resolve([]);

            // 简单分页查询提前量用与判断是否有下一页
            $isSimplePaginateAdvance = $isSimplePaginate ? 1 : 0;

            $empiQueryBuilder = $listSrv
                ->buildQueryConditions(
                    clone $queryEngine,
                    $queryParams,
                    $config,
                    $bizSceneTable
                )
                ->getQueryBuilder();

            // 之前的查询结果只为获取empi(减少回表操作)
            $empis = $empiQueryBuilder
                ->forPage($paginator->currentPage(), $paginator->perPage() + $isSimplePaginateAdvance)
                ->pluck('empi');

            // 根据empi获取完整数据列表
            $items = $this->fetchItems($empis->toArray(), $columns);

            // 附加额外信息
            try {
                if ($items->isNotEmpty()) {
                    $columnKeys = $listSrv->getFinalColumnKeys((string) ($queryParams['original_code'] ?? ($queryParams['code'] ?? '')));
                    $items = AppenderManager::make()->handle($queryEngine, $items, $columnKeys);
                }
            } catch (\Throwable $e) {
                Logger::LOW_CODE_LIST->error('list-query-error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                ]);
            }

            if ($isSimplePaginate) {
                return new LowCodeCustomPaginator(
                    new Paginator($items, $paginator->perPage(), $paginator->currentPage()),
                );
            } else {
                $countQueryBuilder = $listSrv->buildQueryConditions(
                    clone $queryEngine,
                    $queryParams,
                    $config,
                    $bizSceneTable,
                    true
                )->getQueryBuilder();

                // 利用之前的QueryBuilder获取带表前缀(可能)的empi字段名，用于在count时去重
                $empiColumnForPluck = $this->resolveEmpiColumnForPluck($countQueryBuilder, 't2.empi');

                $total = $countQueryBuilder->count(DB::raw('distinct ' . $empiColumnForPluck));

                return new CustomLengthAwarePaginator(
                    new LengthAwarePaginator($items, $total, $paginator->perPage(), $paginator->currentPage()),
                );
            }
        } catch (\Throwable $e) {
            Log::error('分页查询异常：' . $e->getMessage(), ['exception' => $e]);
            throw new QueryEngineException('分页查询异常');
        }
    }

    protected function fetchItems(array $empis, array $columns = []): Collection
    {
        if (empty($empis)) {
            return collect();
        }

        $columns = $columns ?: ['t1.*', 't2.*'];

        $queryEngine = clone $this->customQueryOptions['query_engine'];
        $bizSceneTable = $this->customQueryOptions['biz_scene_table'];

        // 宽表 表名
        $widthTable = config('low-code.bmo-baseline.database.crowd-psn-wdth-table', '');

        // 构建基本的关联查询
        $items = $queryEngine->useTable($widthTable . ' as t1')
            ->leftJoin($bizSceneTable . ' as t2', 't2.empi', '=', 't1.empi')
            ->getQueryBuilder()
            ->whereIn('t1.empi', $empis)
            ->where(fn ($query) => (new EmpiFullFilterTools)($query, ['t1.empi', 't2.empi'], $empis))
            ->select($columns)
            ->get();

        // 保持empis排序
        return $items->sortBy(fn ($item) => array_search($item->empi, $empis))->values();
    }

    /**
     * 解析query中的empi字段(带表前缀的empi字段)
     */
    protected function resolveEmpiColumnForPluck($queryBuilder, string $default = 'empi'): string
    {
        try {
            $baseQuery = method_exists($queryBuilder, 'getQuery') ? $queryBuilder->getQuery() : $queryBuilder;

            // 只有一个表时（无JOIN），根据是否有表别名决定返回值
            if (empty($baseQuery->joins)) {
                $fromRaw = $baseQuery->from ?? null;
                if ($fromRaw instanceof Expression) {
                    $fromRaw = (string) $fromRaw->getValue();
                }
                $from = is_string($fromRaw) ? trim($fromRaw) : '';
                if ($from !== '' && preg_match('/\s+as\s+(`?[a-zA-Z0-9_]+`?)$/i', $from, $matches)) {
                    return trim($matches[1], '`') . '.empi';
                }

                return 'empi';
            }

            $columns = $baseQuery->columns ?? [];

            foreach ($columns as $column) {
                if ($column instanceof Expression) {
                    $column = (string) $column->getValue();
                }

                if (!is_string($column)) {
                    continue;
                }

                $column = trim($column);

                // select t1.empi as empi
                if (preg_match('/^(.+?)\s+as\s+["`]?empi["`]?$/i', $column, $matches)) {
                    $sourceColumn = trim($matches[1]);

                    // select DISTINCT(t2.empi) as empi / DISTINCT t2.empi as empi
                    if (preg_match('/^distinct\s*\(?\s*(`?[a-zA-Z0-9_]+`?\.`?empi`?)\s*\)?$/i', $sourceColumn, $distinctMatches)) {
                        return trim($distinctMatches[1]);
                    }

                    if (str_contains($sourceColumn, '.')) {
                        return $sourceColumn;
                    }
                }

                // select DISTINCT(t2.empi) / DISTINCT t2.empi
                if (preg_match('/^distinct\s*\(?\s*(`?[a-zA-Z0-9_]+`?\.`?empi`?)\s*\)?$/i', $column, $matches)) {
                    return trim($matches[1]);
                }

                // select t1.empi
                if (preg_match('/^`?[a-zA-Z0-9_]+`?\.`?empi`?$/i', $column)) {
                    return $column;
                }
            }
        } catch(Throwable $e) {
            Log::error('解析empi字段异常：' . $e->getMessage());
        }

        return $default;
    }

    public static function of(QueryEngineService $source): self
    {
        $instance = new self();

        $instance->setInstanceProperties($source->getInstanceProperties());

        return $instance;
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
        $empiColumnForPluck = $this->resolveEmpiColumnForPluck($this->queryBuilder, 't2.empi');

        return (int)$this->executeQuery(fn () => $this->queryBuilder->count(DB::raw('distinct ' . $empiColumnForPluck)),
            [], $useCache, $this->randomKey(method: __FUNCTION__));
    }
}
