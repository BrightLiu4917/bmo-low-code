<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Services\LowCode;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use BrightLiu\LowCode\Models\LowCodeList;
use BrightLiu\LowCode\Enums\Foundation\Logger;
use BrightLiu\LowCode\Services\BmoAIApiService;
use BrightLiu\LowCode\Traits\CastDefaultFixHelper;
use BrightLiu\LowCode\Services\LowCodeBaseService;
use Gupo\BetterLaravel\Exceptions\ServiceException;
use BrightLiu\LowCode\Services\DataPermissionService;
use BrightLiu\LowCode\Services\RegionPermissionService;
use BrightLiu\LowCode\Enums\Model\LowCode\LowCodeList\ListTypeEnum;
use BrightLiu\LowCode\Core\TemplatePartCacheManager;
use BrightLiu\LowCode\Services\QueryEngineService;
use BrightLiu\LowCode\Exceptions\QueryEngineException;
use Gupo\BetterLaravel\Database\CustomLengthAwarePaginator;

/**
 * 低代码-列表
 */
class LowCodeListService extends LowCodeBaseService
{
    use CastDefaultFixHelper;

    /**
     * @param  array  $data
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
                'column:name,code,content_type',
                'field:name,code,content_type',
//                'updater:id,realname',
//                'creator:id,realname',
            ]
        )->first();
    }

    public function update(array $data, int $id = 0)
    {
        if (empty(
        $result = LowCodeList::query()->where('id', $id)->first([
            'id',
            'code',
        ])
        )) {
            throw new ServiceException("数据{$id}不存在");
        }
        $filterArgs = $this->fixInputDataByCasts(
            $data,
            LowCodeList::class
        );
        if ($result->update($filterArgs)) {
            TemplatePartCacheManager::clearListCache($result->code);
            return true;
        }
        return false;
    }

    /**
     * @param  int  $id
     *
     * @return bool
     * @throws ServiceException
     */
    public function delete(int $id = 0): bool
    {
        if (!$result = LowCodeList::query()->where('id', $id)->first(
            ['id', 'list_type']
        )) {
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
     * @param  string  $code
     *
     * @return LowCodeList|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder|object|null
     */
    public function pre(string $code = '')
    {
        return TemplatePartCacheManager::getListWithParts($code);
    }

    /**
     * @param  array  $listCodes
     *
     * @return array
     */
    public function getLowCodeListByCodes(array $listCodes = []): array
    {
        return LowCodeList::query()->whereIn(
            'code',
            $listCodes
        )->get([
            'id',
            'crowd_type_code',
            'default_order_by_json',
            'code',
            'preset_condition_json',
            'data_permission_code',
        ])->keyBy('code')->toArray();
    }

    /**
     * @param  array  $inputArgs
     * @param  int  $setCacheTtl  设置缓存时间
     *
     * @return void
     * @throws \Gupo\BetterLaravel\Exceptions\ServiceException
     */
    public function query(array $inputArgs = [], int $setCacheTtl = 10)
    {
        try {
            // 解析code(code中可能携带中台的人群ID)
            $inputArgs = LowCodeCombiService::make()->handleInputArgs(
                $inputArgs
            );

            $listCodes = collect($inputArgs)->pluck('code')->toArray();

            // 1.获取列表
            $list = $this->getLowCodeListByCodes($listCodes);

            $queryEngine   = QueryEngineService::instance()->autoClient();
            $bizSceneTable = $queryEngine->table ?? '';
            foreach ($inputArgs as $value) {
                $listCode = $value['code'] ?? '';
                $config   = $list[$listCode] ?? [];

                //3. 构建查询条件组
                $builtQuery = $this->buildQueryConditions(
                    clone $queryEngine,
                    $value,
                    $config,
                    $bizSceneTable
                );
                return $builtQuery->setCache($setCacheTtl)->getPaginateResult();
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

    /**
     * @param  array  $inputArgs
     * @param  int  $setCacheTtl  设置缓存时间
     *
     * @return void
     * @throws \Gupo\BetterLaravel\Exceptions\ServiceException
     */
    public function queryCount(array $inputArgs = [], int $setCacheTtl = 10)
    {
        try {
            // 解析code(code中可能携带中台的人群ID)
            $inputArgs = LowCodeCombiService::make()->handleInputArgs(
                $inputArgs
            );

            $listCodes = collect($inputArgs)->pluck('code')->toArray();

            // 1.获取列表
            $list = $this->getLowCodeListByCodes($listCodes);

            $queryEngine   = QueryEngineService::instance()->autoClient();
            $bizSceneTable = $queryEngine->table ?? '';
            foreach ($inputArgs as $value) {
                $listCode = $value['code'] ?? '';
                $config   = $list[$listCode] ?? [];

                //3. 构建查询条件组
                $builtQuery = $this->buildQueryConditions(
                    clone $queryEngine,
                    $value,
                    $config,
                    $bizSceneTable
                );
                return $builtQuery->setCache($setCacheTtl)->getCountResult();
            }
        } catch (QueryEngineException $e) {
            Logger::LOW_CODE_LIST->error('低代码列表数量查询异常', [
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
                'line'       => $e->getLine(),
                'file'       => $e->getFile(),
                'input_args' => $inputArgs ?? null,
            ]);
        }
    }

    /**
     * 构建查询条件组
     *
     * @param       $queryEngine
     * @param  array  $queryParams
     * @param  array  $config
     *
     * @return mixed
     */
    private function buildQueryConditions(
        $queryEngine,
        array $queryParams,
        array $config,
        string $bizSceneTable,
    ) {
        try {
            if (empty($queryEngine)) {
                throw new ServiceException(
                    '查询引擎未定义，请检查 入参与配置数据库表配置是否一致'
                );
            }
            $filters = $queryParams['filters'] ?? [];

            //这里是使用api 服务
            $aifilters = [];
            foreach ($filters as $key => $value) {
                $aiMark   = $value[0] ?? '';
                $aiCotent = $value[2] ?? '';
                if (!empty($aiMark) && $aiMark == 'send-ai-service' &&
                    !empty($aiCotent)) {
                    if (config('business.bmo-service.ai.enable', true) ==
                        true) {
                        $aifilters = BmoAIApiService::instance()
                            ->completionSend($aiCotent);
                    }
                    unset($filters[$key]);
                }
            }

            if (!empty($aifilters)) {
                $filters = array_merge($filters, $aifilters);
            }

            // 处理 crowd_id 条件并安全移除
            $crowdIdIndex = Arr::first(
                array_keys($filters),
                fn($key) => isset($filters[$key][0]) &&
                    'crowd_id' === $filters[$key][0]
            );
            $widhtTable   = config(
                'low-code.bmo-baseline.database.crowd-psn-wdth-table'
            );//宽表
            $crowdTable   = config(
                'low-code.bmo-baseline.database.crowd-type-table'
            );//人群表

            if (null !== $crowdIdIndex) {
                $conditionOfCrowd = $filters[$crowdIdIndex];
                $queryEngine->useTable($crowdTable.' as t3')//重新引入基表
                    ->innerJoin($widhtTable.' as t1', 't3.empi', '=', 't1.empi')
                    ->leftJoin(
                        $bizSceneTable.' as t2',
                        't3.empi',
                        '=',
                        't2.empi'
                    )->select(
                        [
                            't2.*',
                            't1.*',
                        ]
                    )->whereMixed([['t3.group_id', '=', $conditionOfCrowd[2]]]);
                unset($filters[$crowdIdIndex]);
            } else {
                $t1Empi      = 't1.empi';
                $queryEngine = $queryEngine->useTable($bizSceneTable.' as t2')
                    ->innerJoin("$widhtTable as t1", $t1Empi, '=', 't2.empi');

            }


            // 安全合并预设条件
            $presetCondition = $config['preset_condition_json'] ?? [];
            if (!empty($presetCondition)) {
                $filters = array_merge(
                    $filters,
                    array_filter($presetCondition)
                );
            }

            if (!empty($filters)) {
                $queryEngine->whereMixed($filters);
            }

            //数据权限条件
            $dataPermissionCode = $config['data_permission_code'] ?? '';
            Logger::DATA_PERMISSION_ERROR->debug('low-code-list-service-data-permission',['data_permission_code'=>$dataPermissionCode]);
            if ($dataPermissionCode !== '') {
                $dataPermissionCondition = DataPermissionService::instance()
                    ->channel($dataPermissionCode)
                    ->run();
                Logger::DATA_PERMISSION_ERROR->debug('low-code-list-service-data-permission-get-result',['data_permission_code'=>$dataPermissionCode]);
                if (!empty($dataPermissionCondition)) {
                    $queryEngine->whereMixed($dataPermissionCondition);
                }
            }

            // 合并排序条件
            $inputOrderBy   = $queryParams['order_by'] ?? [];
            $defaultOrderBy = $config['default_order_by_json'] ?? [];
            $queryEngine->multiOrderBy(
                array_merge($inputOrderBy, $defaultOrderBy)
            );
            return $queryEngine;
        } catch (\Throwable $exception) {
            Logger::LOW_CODE_LIST->error(
                '低代码列表查询异常-buildQueryConditions',
                [
                    'error' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString(),
                    'line'  => $exception->getLine(),
                    'file'  => $exception->getFile(),
                ]
            );
        }
    }
}
