<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Services;



use BrightLiu\LowCode\Tools\Tree;
use Illuminate\Support\Facades\DB;
use BrightLiu\LowCode\Tools\Region;
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
    public static function getBatchRegionLevel(array $codes = []):array
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
    public function getRegionDataByConfigRegionCode(string $usePermission = '',array $targetCodes):array
    {
        $useRegionCode = config('low-code.use-region-code');
        $connection = config('low-code-database.region');
        //根据地区编码查询地区数据
        $regionTable = data_get($connection, 'table','mdm_admnstrt_rgn_y');
        $lists = Db::connection($connection)
            ->table($regionTable)
            ->where('prm_key','like',"%{$useRegionCode}%")
            ->where('invld_flg','=',0)
            ->get();

        //空数据
        if (empty($lists)){
            return [];
        }
        $data = $lists->toArray();

        //使用权限
        if (!empty($usePermission)){
           return Tree::listToTree($data,'prm_key','pre_cd','children');
        }
        if (empty($targetCodes)){
            $targetCodes = $this->getManageAreaCodes();
        }
        return Tree::buildRegionTree($data,$targetCodes,'prm_key','pre_cd','children');
    }
}
