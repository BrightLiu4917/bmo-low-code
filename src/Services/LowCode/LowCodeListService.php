<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Services\LowCode;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Models\LowCode\LowCodeList;
use BrightLiu\LowCode\Enums\Foundation\Logger;
use BrightLiu\LowCode\Traits\CastDefaultFixHelper;
use BrightLiu\LowCode\Services\LowCodeBaseService;
use Gupo\BetterLaravel\Exceptions\ServiceException;
use BrightLiu\LowCode\Enums\Model\LowCodeList\ListTypeEnum;
use BrightLiu\LowCode\Core\TemplatePartCacheManager;
use App\Services\Common\QueryEngine\QueryEngineService;
use BrightLiu\LowCode\Exceptions\QueryEngineException;
use App\Services\LowCode\LowCodeQueryEngineService;
use Gupo\BetterLaravel\Database\CustomLengthAwarePaginator;

/**
 * 低代码-列表
 */
class LowCodeListService extends LowCodeBaseService
{
    use CastDefaultFixHelper;

    /**
     * @param array $data
     *
     * @return LowCodeList|null
     */
    public function create(array $data = []): LowCodeList|null
    {
        $filterArgs = $this->fixInputDataByCasts($data, LowCodeList::class);
        return LowCodeList::query()->create($filterArgs);
    }

    public function show(int $id = 0): LowCodeList|null
    {
        if ($id <= 0) {
            return null; // 防止无效id继续查询和缓存
        }
        return LowCodeList::query()->where('id', $id)->with(
            [
                'filter:name,code,content_type',
                'button:name,code,content_type',
                'topButton:name,code,content_type',
                'column:name,code,content_type', 'field:name,code,content_type',
                'updater:id,realname', 'creator:id,realname',
            ]
        )->first();
    }

    public function update(array $data, int $id = 0)
    {
        if (empty($result = LowCodeList::query()->where('id', $id)->first([
            'id', 'code',
        ]))) {
            throw new ServiceException("数据{$id}不存在");
        }
        $filterArgs = $this->fixInputDataByCasts(
            $data, LowCodeList::class
        );
        if ($result->update($filterArgs)) {
            TemplatePartCacheManager::clearListCache($result->code);
            return true;
        }
        return false;
    }

    /**
     * @param int $id
     *
     * @return bool
     * @throws ServiceException
     */
    public function delete(int $id = 0): bool
    {
        if (!$result = LowCodeList::query()->where('id', $id)->first(
            ['id', 'list_type']
        )
        ) {
            throw new ServiceException("ID:{$id}不存在");
        }

        if ($result->list_type == ListTypeEnum::GENERAL) {
            throw new ServiceException(
                "通用列表不支持删除,删了就无法自动生成列表"
            );
        }
        return $result->delete();
    }

    /**
     * @param string $code
     *
     * @return LowCodeList|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder|object|null
     */
    public function pre(string $code = '')
    {
        return TemplatePartCacheManager::getListWithParts($code);
    }

    /**
     * @param array $listCodes
     *
     * @return array
     */
    public function getLowCodeListByCodes(array $listCodes = []):array
    {
        return LowCodeList::query()->whereIn(
            'code', $listCodes
        )->get([
            'id', 'crowd_type_code', 'default_order_by_json', 'code',
            'preset_condition_json',
        ])->keyBy('code')->toArray();
    }

    /**
     * 构建查询条件组
     * @param       $queryEngine
     * @param array $queryParams
     * @param array $config
     *
     * @return mixed
     */
    private function buildQueryConditions($queryEngine, array $queryParams, array $config)
    {
        $filters = $queryParams['filters'] ?? [];

        // 处理 crowd_id 条件并安全移除
        $crowdIdIndex = Arr::first(array_keys($filters), fn ($key) => isset($filters[$key][0]) && 'crowd_id' === $filters[$key][0]);
        if (null !== $crowdIdIndex) {
            $conditionOfCrowd = $filters[$crowdIdIndex];
            // 使用参数绑定防止SQL注入
            $queryEngine = $queryEngine->rawTable(sprintf(
                    '(SELECT t1.*, t2.`group_id` FROM %s AS t1 INNER JOIN feature_user_detail AS t2 ON t1.user_id = t2.user_id where %s) as t',
                    $queryEngine->table,
                    "t2.group_id = '{$conditionOfCrowd[2]}'"
                )
            );
            unset($filters[$crowdIdIndex]);
        }

        // 安全合并预设条件
        $presetCondition = $config['preset_condition_json'] ?? [];
        if (!empty($presetCondition)) {
            $filters = array_merge($filters, array_filter($presetCondition));
        }

        if (!empty($filters)) {
            $queryEngine->whereMixed($filters);
        }

        // 合并排序条件
        $inputOrderBy = $queryParams['order_by'] ?? [];
        $defaultOrderBy = $config['default_order_by_json'] ?? [];
        $queryEngine->multiOrderBy(array_merge($inputOrderBy, $defaultOrderBy));

        return $queryEngine;
    }

    /**
     *
     * @param array $inputArgs
     * @param array $params
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|void
     * @throws ServiceException
     */
    public function query(array $inputArgs = [])
    {
        try {
            // 1.获取列表
            $list = $this->getLowCodeListByCodes(collect($inputArgs)->pluck('code')->toArray());

            // 2.初始化查询
            $queryEngine = LowCodeQueryEngineService::instance()->autoClient();
            foreach ($inputArgs as $value) {
                $listCode = $value['code'] ?? '';
                $config = $list[$listCode] ?? [];

                //3. 构建查询条件组
                $builtQuery = $this->buildQueryConditions($queryEngine, $value, $config);
                return $builtQuery->setCache(60)->getPaginateResult();
            }
        } catch (QueryEngineException $e) {
            Logger::LOW_CODE_LIST->error('低代码列表查询异常', [
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
                'line'       => $e->getLine(),
                'file'       => $e->getFile(),
                'input_args' => $inputArgs ?? null,
            ]);
        }
    }
}
