<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Services\LowCode;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use BrightLiu\LowCode\Models\LowCodeList;
use BrightLiu\LowCode\Enums\Foundation\Logger;
use BrightLiu\LowCode\Traits\CastDefaultFixHelper;
use BrightLiu\LowCode\Services\LowCodeBaseService;
use Gupo\BetterLaravel\Exceptions\ServiceException;
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
    private function buildQueryConditions($queryEngine, array $queryParams, array $config,string $bizSceneTable)
    {
        try {
            $filters = $queryParams['filters'] ?? [];

            // 处理 crowd_id 条件并安全移除
            $crowdIdIndex = Arr::first(array_keys($filters), fn ($key) => isset($filters[$key][0]) && 'crowd_id' === $filters[$key][0]);
            $widhtTable = config('low-code.bmo-baseline.database.crowd-psn-wdth-table');//宽表
            $crowdTable = config('low-code.bmo-baseline.database.crowd-type-table');//人群表

            if (null !== $crowdIdIndex) {
                $conditionOfCrowd = $filters[$crowdIdIndex];
                $queryEngine
                    ->useTable($crowdTable.' as t3')//重新引入基表
                    ->innerJoin($widhtTable.' as t1','t3.empi', '=', 't1.empi')
                    ->leftJoin($bizSceneTable.' as t2','t3.empi', '=', 't2.empi')
                    ->whereMixed([['t3.group_id', '=', $conditionOfCrowd[2]]]);
                /**
                 * feature_user_detail t3
                 * 宽t1
                 * 场景表 t2
                 */


                //                $queryEngine = $queryEngine->rawTable(
                //                    sprintf(
                //                        "(SELECT t3.`group_id`,t1.*,t2.* FROM {$crowdTable} AS t3 INNER JOIN %s AS t1 ON t3.empi = t1.empi LEFT JOIN %s AS t2 ON t3.empi = t2.empi where %s) as t",
                //                        $widhtTable,
                //                        $queryEngine->table,
                //                        "t3.group_id = '{$conditionOfCrowd[2]}'"
                //                    )
                //                );

                // 从$queryItem中移除crowd_id相关条件
                unset($filters[$crowdIdIndex]);
            } else  {
                $t1 = $queryEngine->table;
                $t1Empi = $queryEngine->table.'.empi';
                $queryEngine = $queryEngine->rightJoin("$widhtTable as t2", $t1Empi, '=', 't2.empi');

                //todo 缺陷 join 后不支持子查询
                //                    ->rawTable(
                //                    sprintf(
                //                        '(
                //                        SELECT
                //                            t1.*,
                //                            t2.*
                //                        FROM
                //                            %s AS t1
                //                        LEFT JOIN %s AS t2
                //                            ON t1.empi = t2.empi) as t',
                //                        $widhtTable,
                //                        $queryEngine->table
                //                    )
                //                );
            }
            //        if (null !== $crowdIdIndex) {
            //            $conditionOfCrowd = $filters[$crowdIdIndex];
            //            // 使用参数绑定防止SQL注入
            //            $queryEngine = $queryEngine->rawTable(sprintf(
            //                    '(SELECT t1.*, t2.`group_id` FROM %s AS t1 INNER JOIN feature_user_detail AS t2 ON t1.user_id = t2.user_id where %s) as t',
            //                    $queryEngine->table,
            //                    "t2.group_id = '{$conditionOfCrowd[2]}'"
            //                )
            //            );
            //            unset($filters[$crowdIdIndex]);
            //        }

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
        }catch (\Throwable $exception){
            Logger::LOW_CODE_LIST->error('低代码列表查询异常-buildQueryConditions', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'line'  => $exception->getLine(),
                'file'  => $exception->getFile(),
            ]);
        }
    }

    /**
     * @param array $inputArgs
     * @param int   $setCacheTtl 设置缓存时间
     *
     * @return void
     * @throws \Gupo\BetterLaravel\Exceptions\ServiceException
     */
    public function query(array $inputArgs = [],int $setCacheTtl = 10)
    {
        try {
            // 1.获取列表
            $list = $this->getLowCodeListByCodes(collect($inputArgs)->pluck('code')->toArray());

            $queryEngine = QueryEngineService::instance()->autoClient();
            $bizSceneTable = $queryEngine->table ?? '';
            foreach ($inputArgs as $value) {
                $listCode = $value['code'] ?? '';
                $config = $list[$listCode] ?? [];

                //3. 构建查询条件组
                $builtQuery = $this->buildQueryConditions($queryEngine, $value, $config,$bizSceneTable);
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
     * @param string|array $codes
     *
     * @return string|array
     */
    public function covertCrowdPatientCode(string|array $codes): string|array
    {
        return Cache::remember('crowd_patient_code:'.md5(json_encode($codes)),
            60 * 5, function() use ($codes) {
                $isOnce = !is_array($codes);

                $codes = (array)$codes;

                $existsCodes = LowCodeList::query()->whereIn('code', $codes)
                                          ->pluck('code')->toArray();

                // TODO: 写法待完善
                $crowdPatientCode = LowCodeList::query()->byContextDisease()
                                               ->where('admin_name',
                                                   '人群患者列表')
                                               ->value('code');

                return transform(
                    array_combine($codes,
                        array_map(fn ($code) => in_array($code, $existsCodes) ?
                            $code : $crowdPatientCode, $codes)),
                    fn ($value) => $isOnce ? end($value) ?? '' : $value
                );
            });
    }
}
