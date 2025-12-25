<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Services;


use BrightLiu\LowCode\Tools\Tree;
use Illuminate\Support\Facades\DB;
use BrightLiu\LowCode\Tools\Region;
use Illuminate\Support\Facades\Cache;
use BrightLiu\LowCode\Enums\Foundation\Logger;
use BrightLiu\LowCode\Core\DbConnectionManager;
use BrightLiu\LowCode\Traits\Context\WithContext;

/**
 * @Class
 * @Description:
 * @created: 2025-10-29 20:27:18
 * @modifier: 2025-10-29 20:27:18
 */
class RegionService extends LowCodeBaseService
{
    use WithContext;

    public static function getBatchRegionLevel(array $codes = []): array
    {
        // 初始化返回结构
        $result = [
            'prv'  => [],
            'cty'  => [],
            'cnty' => [],
            'twn'  => [],
            'vlg'  => [],
        ];

        if (empty($codes)) {
            return $result;
        }
        foreach ($codes as $code) {
            $level = Region::regionLevel($code);
            // 只有当regionLevel返回非空字符串时才添加
            if (!empty($level) && isset($result[$level])) {
                $result[$level][] = $code;
            }
        }
        return $result;
    }

    /**
     * @param  string  $usePermission
     * @param  array  $targetCodes
     *
     * @return array
     */
    public function getRegionDataByConfigRegionCode(
        ?string $usePermission = null,
        array $targetCodes = [],
    ): array {
        try {
            $data = $this->getRegionAllData();

            //空数据
            if (empty($data)) {
                return [];
            }

            //使用权限
            //            $targetCodes = [
            //                '460203198008','460204000000'
            //            ];

            //            dd(Tree::buildRegionTree($data,$targetCodes,'value','parent_code','children'));
            if (empty($usePermission)) {
                return Tree::buildRegionTree(
                    $data,
                    $targetCodes,
                    'value',
                    'parent_code',
                    'children'
                );
            }

            if (empty($targetCodes)) {
                $targetCodes = $this->aggregateRegionCode(
                    RegionService::instance()->getDataPermissionManageOrgArr()
                        //->getManageAreaCodes()
                );
            }

            if (empty($targetCodes)) {
                return [];
            }
            return Tree::buildRegionTree(
                $data,
                $targetCodes,
                'value',
                'parent_code',
                'children'
            );
        } catch (\Throwable $throwable) {
        }
        return [];
    }

    public function getRegionAllData()
    {
        $useRegionCode    = config('low-code.use-region-code');
        $connectionConfig = config('low-code-database.region');
        $regionTable      = data_get(
            $connectionConfig,
            'table',
            'mdm_admnstrt_rgn_y'
        );

        // 生成缓存键
        $cacheKey = md5(('region-data-'.$useRegionCode.$regionTable));

        // 缓存时间（可配置，默认60分钟）
        $cacheTtl = config('low-code.region-cache-ttl', 60);

        try {
            // 尝试从缓存获取数据
            $lists = Cache::remember(
                $cacheKey,
                $cacheTtl,
                function () use (
                    $useRegionCode,
                    $regionTable,
                    $connectionConfig
                ) {
                    return DbConnectionManager::getInstance()->getConnection(
                        'region',
                        $connectionConfig
                    )->table($regionTable)->where(
                            'prm_key',
                            'like',
                            "{$useRegionCode}%"
                        )
                        ->where('invld_flg', '=', 0)
                        ->where('admnstrt_rgn_nm',"<>","市辖区")
                        ->select(
                            DB::raw(
                                "
                                        prm_key as value,
                                        prm_key as code,
                                        admnstrt_rgn_nm as label,
                                        admnstrt_rgn_nm as name,
                                        pre_cd as parent_code,
                                        lvl_flg as level,
                                        CASE 
                                            WHEN lvl_flg = 1 THEN 'province'
                                            WHEN lvl_flg = 2 THEN 'city'
                                            WHEN lvl_flg = 3 THEN 'district' 
                                            WHEN lvl_flg = 4 THEN 'town'
                                            WHEN lvl_flg = 5 THEN 'community'
                                            ELSE 'unknown'
                                        END as type
                                    "
                            )
                        )->get()->map(function ($item) {
                            return (array)$item;
                        })->toArray();
                }
            );

            return $lists;
        } catch (\Exception $e) {
            // 如果查询失败，尝试从缓存获取旧数据
            Logger::REGION_PERMISSION_ERROR->error(
                '地区数据查询失败，尝试使用缓存',
                ['error' => $e->getMessage()]
            );

            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            throw $e;
        }
    }

    /**
     * @param  array  $codes
     *
     * @return array
     */
    public function aggregateRegionCode(array $codes = []): array
    {
        return collect($codes)->values()->flatten(1)->values()->toArray();
    }


    /**
     * @param  string  $key
     * @param  string  $index
     *
     * @return array
     * @throws \Exception
     */
    public function getRegionDataByColumn(string $key = 'name',string $index = 'code')
    {
        return array_column($this->getRegionAllData(), $key,$index);
    }
}
