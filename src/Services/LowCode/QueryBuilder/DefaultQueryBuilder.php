<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\LowCode\QueryBuilder;

use BrightLiu\LowCode\Core\Abstracts\QueryEngineAbstract;
use BrightLiu\LowCode\Enums\Foundation\Logger;
use BrightLiu\LowCode\Services\BmoAIApiService;
use BrightLiu\LowCode\Services\Contracts\ILowCodeQueryBuilder;
use BrightLiu\LowCode\Services\DataPermissionService;
use BrightLiu\LowCode\Services\QueryEngineService;
use Gupo\BetterLaravel\Exceptions\ServiceException;
use Gupo\BetterLaravel\Service\BaseService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;

class DefaultQueryBuilder extends BaseService implements ILowCodeQueryBuilder
{
    protected QueryEngineAbstract $queryEngine;

    protected array $queryParams;

    protected array $config;

    protected string $bizSceneTable;

    /**
     * @param QueryEngineAbstract $queryEngine 查询引擎
     * @param array $queryParams 查询参数
     * @param array $config 低代码配置
     * @param string $bizSceneTable 业务场景表 表名
     */
    public function __invoke(
        QueryEngineAbstract $queryEngine,
        array $queryParams,
        array $config,
        string $bizSceneTable
    ): QueryEngineAbstract {
        $this->queryEngine = $queryEngine;
        $this->queryParams = $queryParams;
        $this->config = $config;
        $this->bizSceneTable = $bizSceneTable;

        // 前置准备
        $filters = $this->prepare();

        try {
            // 构建基本的关联查询
            $this->relationQueryEngine($filters);

            // 合并来自config中的预设条件
            $filters = $this->mergeConfigPresetCondition($filters);

            // 应用过滤条件
            if (!empty($filters)) {
                $this->applyFilters($filters);
            }

            // 附加数据权限条件
            $this->attachDataPermissionCondition();

            // 应用排序规则
            $this->applyOrderBy();

            return $this->queryEngine;
        } catch (\Throwable $e) {
            Logger::LOW_CODE_LIST->error(
                '低代码列表查询异常-buildQueryConditions',
                [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                ]
            );
        }
    }

    /**
     * 前置准备
     * PS: 主要处理一些历史遗留功能
     *
     * @throws ServiceException
     */
    public function prepare(): array
    {
        if (empty($this->queryEngine)) {
            throw new ServiceException('查询引擎未定义，请检查 入参与配置数据库表配置是否一致');
        }

        $filters = (array) ($this->queryParams['filters'] ?? []);

        $actions = $this->prepareActions();
        foreach ($actions as $action) {
            $filters = $this->{$action}($filters);
        }

        return $filters;
    }

    /**
     * 声明: 前置准备操作
     */
    public function prepareActions(): array
    {
        return ['prepareAiFilter'];
    }

    /**
     * 从AI结果附加查询条件
     * PS: 移植自原有功能，只进行了代码风格对齐调整
     */
    public function prepareAiFilter(array $filters): array
    {
        // 这里是使用 AI 服务
        $aiFilters = [];
        foreach ($filters as $key => $value) {
            $aiMark = $value[0] ?? '';
            $aiContent = $value[2] ?? '';
            if (!empty($aiMark) && 'send-ai-service' == $aiMark && !empty($aiContent)) {
                if (true == config('business.bmo-service.ai.enable', true)) {
                    $aiFilters = BmoAIApiService::instance()->completionSend($aiContent);
                }
                unset($filters[$key]);
            }
        }

        if (!empty($aiFilters)) {
            $filters = array_merge($filters, $aiFilters);
        }

        return $filters;
    }

    /**
     * 构建基本的关联查询
     */
    public function relationQueryEngine(array $filters): void
    {
        // 宽表 表名
        $widthTable = config('low-code.bmo-baseline.database.crowd-psn-wdth-table', '');

        // 人群表 表名
        $crowdTable = config('low-code.bmo-baseline.database.crowd-type-table', '');

        // 提取并转换“人群分类”条件作为标准的查询条件
        $crowdIdIndex = Arr::first(
            array_keys($filters),
            fn ($key) => isset($filters[$key][0]) && 'crowd_id' === $filters[$key][0]
        );
        if (!empty($conditionOfCrowd = $filters[$crowdIdIndex] ?? null)) {
            unset($filters[$crowdIdIndex]);
            $filters[] = ['t3.group_id', '=', $conditionOfCrowd[2]];
        }

        // 构建基本的关联查询
        $this->queryEngine->useTable($crowdTable . ' as t3')
            ->innerJoin($widthTable . ' as t1', 't3.empi', '=', 't1.empi')
            ->leftJoin($this->bizSceneTable . ' as t2', 't3.empi', '=', 't2.empi')
            ->select(['t2.*', 't1.*']);
    }

    /**
     * 合并来自config中的预设条件
     */
    public function mergeConfigPresetCondition(array $filters): array
    {
        if (!empty($presetCondition = $this->config['preset_condition_json'] ?? [])) {
            $filters = array_merge(
                $filters,
                array_filter($presetCondition)
            );
        }

        return $filters;
    }

    /**
     * 应用过滤条件
     */
    public function applyFilters(array $filters): void
    {
        // 提交 QueryEngine 处理混合查询条件
        /** @var QueryEngineService $mixedQueryEngine */
        $mixedQueryEngine = tap(
            QueryEngineService::make(),
            function (QueryEngineService $query) use ($filters) {
                $query->setQueryBuilder($this->queryEngine->getQueryBuilder()->newQuery());
                $query->whereMixed($filters);
            }
        );

        // 应用混合查询条件
        $this->queryEngine->getQueryBuilder()
            ->whereExists(fn (Builder $query) => $query->from($this->bizSceneTable, 't2')
                ->selectRaw('1')
                ->whereRaw('t2.empi = t3.empi')
                ->addNestedWhereQuery($mixedQueryEngine->getQueryBuilder())
            );
    }

    /**
     * 应用排序规则
     */
    public function applyOrderBy(): void
    {
        // 输入的排序规则
        $inputOrderBy = $this->queryParams['order_by'] ?? [];

        // 预设的排序规则
        $defaultOrderBy = $this->config['default_order_by_json'] ?? [];

        $this->queryEngine->multiOrderBy(
            array_merge($inputOrderBy, $defaultOrderBy)
        );
    }

    /**
     * 解析数据权限条件
     */
    public function resolveDataPermissionCondition(): array
    {
        // 数据权限条件
        $dataPermissionCode = $this->config['data_permission_code'] ?? '';

        $dataPermissionCode = 'region_and_referral';

        Logger::DATA_PERMISSION_ERROR->debug(
            'low-code-list-service-data-permission',
            ['data_permission_code' => $dataPermissionCode]
        );

        $dataPermissionCondition = [];
        if ('' !== $dataPermissionCode) {
            $dataPermissionCondition = DataPermissionService::instance()->channel($dataPermissionCode)->run();

            Logger::DATA_PERMISSION_ERROR->debug(
                'low-code-list-service-data-permission-get-result',
                ['data_permission_code' => $dataPermissionCode, 'data_permission_condition' => $dataPermissionCondition]
            );
        }

        return $dataPermissionCondition;
    }

    /**
     * 附加数据权限条件
     */
    public function attachDataPermissionCondition(): void
    {
        if (!empty($dataPermissionCondition = $this->resolveDataPermissionCondition())) {
            $this->queryEngine->whereMixed($dataPermissionCondition);
        }
    }
}
