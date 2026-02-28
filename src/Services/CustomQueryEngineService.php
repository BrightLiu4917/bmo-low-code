<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services;

use BrightLiu\LowCode\Enums\Foundation\Logger;
use BrightLiu\LowCode\Exceptions\QueryEngineException;
use BrightLiu\LowCode\Services\LowCode\LowCodeListService;
use Gupo\BetterLaravel\Database\CustomLengthAwarePaginator;
use Gupo\BetterLaravel\Database\CustomPaginator;
use Illuminate\Contracts\Pagination\Paginator as IPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

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
     *
     * @return IPaginator 分页结果
     *
     * @throws QueryEngineException 如果分页查询异常
     */
    public function getPaginateResult(bool $isSimplePaginate = false): IPaginator
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

            // 之前的查询结果只为获取empi(减少回表操作)
            $empis = $listSrv
                ->buildQueryConditions(
                    clone $queryEngine,
                    $queryParams,
                    $config,
                    $bizSceneTable
                )
                ->getQueryBuilder()
                ->forPage($paginator->currentPage(), $paginator->perPage())
                ->pluck('empi');

            // 根据empi获取完整数据列表
            $items = $this->fetchItems($empis->toArray());

            // 附加额外信息
            try {
                $items = $this->attachCrowdGroup($items);
            } catch (\Throwable $e) {
                Logger::LOW_CODE_LIST->error('list-query-error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                ]);
            }

            if ($isSimplePaginate) {
                return new CustomPaginator(
                    new Paginator($items, $paginator->perPage(), $paginator->currentPage()),
                );
            } else {
                $total = $listSrv
                    ->buildQueryConditions(
                        clone $queryEngine,
                        $queryParams,
                        $config,
                        $bizSceneTable,
                        true
                    )
                    ->getQueryBuilder()
                    ->count();

                return new CustomLengthAwarePaginator(
                    new LengthAwarePaginator($items, $total, $paginator->perPage(), $paginator->currentPage()),
                );
            }
        } catch (\Throwable $e) {
            Log::error('分页查询异常：' . $e->getMessage(), ['exception' => $e]);
            throw new QueryEngineException('分页查询异常');
        }
    }

    protected function fetchItems(array $empis): Collection
    {
        if (empty($empis)) {
            return collect();
        }

        $queryEngine = clone $this->customQueryOptions['query_engine'];
        $bizSceneTable = $this->customQueryOptions['biz_scene_table'];

        // 宽表 表名
        $widthTable = config('low-code.bmo-baseline.database.crowd-psn-wdth-table', '');

        // 构建基本的关联查询
        $items = $queryEngine->useTable($widthTable . ' as t1')
            ->innerJoin($bizSceneTable . ' as t2', 't2.empi', '=', 't1.empi')
            ->getQueryBuilder()
            ->whereIn('t1.empi', $empis)
            ->select(['t2.*', 't1.*'])
            ->get();

        // 保持empis排序
        return $items->sortBy(fn ($item) => array_search($item->empi, $empis))->values();
    }

    /**
     * 附加人群分类信息
     */
    protected function attachCrowdGroup(Collection $items): Collection
    {
        if (empty($items)) {
            return $items;
        }

        if (empty($empis = $items->pluck('empi')->unique()->toArray())) {
            return $items;
        }

        $queryEngine = clone $this->customQueryOptions['query_engine'];

        $featureCrowdTable = config('low-code.bmo-baseline.database.crowd-type-table', 'feature_crowd_type');
        $userGroupTable = config('low-code.bmo-baseline.database.crowd-group-table', 'user_group');

        // 按empi连表查询人群分类信息(一个人可能属于多个人群分类)
        $crowdGroups = $queryEngine->useTable($featureCrowdTable . ' as t1')
            ->innerJoin($userGroupTable . ' as t2', 't2.id', '=', 't1.group_id')
            ->getQueryBuilder()
            ->whereIn('t1.empi', $empis)
            ->where('t2.is_deleted', 0)
            ->select(['t1.empi', 't2.group_name', 't2.select_type'])
            ->get()
            ->groupBy('empi');

        return $items->map(function ($item) use ($crowdGroups) {
            if (isset($crowdGroups[$item->empi])) {
                $userCrowdGroups = collect($crowdGroups[$item->empi] ?? null);
                $item->_crowds = $userCrowdGroups
                    // select_type=9为基线人群，排除再外
                    ->where('select_type', '<>', 9)
                    ->pluck('group_name')
                    ->unique()
                    ->join(',');
            }

            return $item;
        });
    }

    public static function of(QueryEngineService $source): self
    {
        $instance = new self();

        $instance->setInstanceProperties($source->getInstanceProperties());

        return $instance;
    }
}
