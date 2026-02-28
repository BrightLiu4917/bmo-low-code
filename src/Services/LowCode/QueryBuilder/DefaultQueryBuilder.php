<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\LowCode\QueryBuilder;

use BrightLiu\LowCode\Context\OrgContext;
use BrightLiu\LowCode\Core\Abstracts\QueryEngineAbstract;
use BrightLiu\LowCode\Enums\Foundation\Logger;
use BrightLiu\LowCode\Services\BmoAIApiService;
use BrightLiu\LowCode\Services\Contracts\ILowCodeQueryBuilder;
use BrightLiu\LowCode\Services\CustomQueryEngineService;
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

    protected bool $isQueryCount = false;

    protected array $customSearchActions = [];

    /**
     * @param QueryEngineAbstract $queryEngine 查询引擎
     * @param array $queryParams 查询参数
     * @param array $config 低代码配置
     * @param string $bizSceneTable 业务场景表 表名
     * @param bool $isQueryCount 是否为计数查询
     */
    public function __invoke(
        QueryEngineAbstract $queryEngine,
        array $queryParams,
        array $config,
        string $bizSceneTable,
        bool $isQueryCount = false
    ): bool|QueryEngineAbstract {
        $this->queryEngine = $queryEngine;
        $this->queryParams = $queryParams;
        $this->config = $config;
        $this->bizSceneTable = $bizSceneTable;
        $this->isQueryCount = $isQueryCount;

        // 前置准备
        $filters = $this->prepare();

        try {
            // 合并来自config中的预设条件
            $filters = $this->mergeConfigPresetCondition($filters);

            // 解析出自定义搜索动作
            ['actions' => $searchActions, 'filters' => $filters] = $this->resolveCustomSearchActions($filters);
            $this->setCustomSearchActions($searchActions);

            // 构建基本的关联查询
            $filters = $this->relationQueryEngine($filters);

            // 应用过滤条件
            if (!empty($filters)) {
                $this->applyFilters($filters);
            }

            // 附加数据权限条件
            $this->attachDataPermissionCondition();

            // 处理自定义搜索动作
            if (!empty($searchActions)) {
                $this->handleCustomSearchActions($searchActions, $filters);
            }

            // 应用排序规则
            $this->applyOrderBy();

            return CustomQueryEngineService::of($this->queryEngine);
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

        return false;
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
        return ['prepareAiFilter', 'prepareCrowdGroup'];
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
     * 将crowd_id转换为group_id条件
     */
    public function prepareCrowdGroup(array $filters): array
    {
        // 提取并转换“人群分类”条件作为标准的查询条件
        $crowdIdIndex = Arr::first(
            array_keys($filters),
            fn ($key) => isset($filters[$key][0]) && 'crowd_id' === $filters[$key][0]
        );
        if (!empty($conditionOfCrowd = $filters[$crowdIdIndex] ?? null)) {
            unset($filters[$crowdIdIndex]);
            $filters[] = ['t3.group_id', '=', $conditionOfCrowd[2]];
            $filters = array_values($filters);
        }

        return $filters;
    }

    /**
     * 构建基本的关联查询
     */
    public function relationQueryEngine(array $filters): array
    {
        // 宽表 表名
        $widthTable = config('low-code.bmo-baseline.database.crowd-psn-wdth-table', '');

        // 人群表 表名
        $crowdTable = config('low-code.bmo-baseline.database.crowd-type-table', '');

        // 构建基本的关联查询
        $this->queryEngine->useTable($crowdTable . ' as t3')
            ->innerJoin($widthTable . ' as t1', 't3.empi', '=', 't1.empi')
            ->leftJoin($this->bizSceneTable . ' as t2', 't3.empi', '=', 't2.empi')
            ->select(['t1.empi']);

        return $filters;
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
        if (!config('low-code.data-permission-enabled', true)) {
            return;
        }

        if (!empty($dataPermissionCondition = $this->resolveDataPermissionCondition())) {
            if (
                empty(OrgContext::instance()->getDataPermissionManageAreaArr())
                && empty(OrgContext::instance()->getDataPermissionManageOrgArr())
            ) {
                throw new \Exception('暂无数据权限');
            }

            $this->queryEngine->whereMixed($dataPermissionCondition);
        }
    }

    /**
     * 解析出自定义搜索动作，并从过滤条件中移除相关条件
     */
    protected function resolveCustomSearchActions(array $filters): array
    {
        $prefix = '_c.';
        $customActions = [];
        $cleanedFilters = [];

        // 转换filters为统一的字符串格式便于检查
        $stringifiedFilters = array_map(
            fn ($item) => is_array($item) ? join($item) : $item,
            $filters
        );

        // 遍历原始filters，分离自定义搜索动作和普通条件
        foreach ($filters as $index => $item) {
            $stringItem = $stringifiedFilters[$index];

            // 检查是否为自定义搜索动作条件
            if (!empty($stringItem) && is_string($stringItem) && str_starts_with($stringItem, $prefix)) {
                // 解析自定义搜索动作
                preg_match('/^' . preg_quote($prefix, '/') . '(.*?)\=(.*?)$/', $stringItem, $matches);

                if (!empty($matches[1]) && !empty($matches[2])) {
                    $customActions[] = sprintf('%s:%s', $matches[1], $matches[2]);
                }
            } else {
                // 保留非自定义搜索动作的条件
                $cleanedFilters[] = $item;
            }
        }

        return [
            'actions' => $customActions,
            'filters' => $cleanedFilters,
        ];
    }

    public function setCustomSearchActions(array $actions): void
    {
        $this->customSearchActions = $actions;
    }

    public function getCustomSearchActions(): array
    {
        return $this->customSearchActions;
    }

    public function hasCustomSearchAction(string|array $action): bool
    {
        $actions = $this->getCustomSearchActions();

        if (is_string($action)) {
            return in_array($action, $actions, true);
        }

        if (is_array($action)) {
            return count(array_intersect($action, $actions)) > 0;
        }

        return false;
    }

    /**
     * 处理自定义搜索动作
     */
    protected function handleCustomSearchActions(array $searchActions, array $filters): void
    {
    }
}
