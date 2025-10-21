<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Services\LowCode;


use BrightLiu\LowCode\Services\LowCodeBaseService;
use App\Services\LowCode\LowCodeQueryEngineService;


/**
 * @Class
 * @Description:
 * @created    : 2025-10-20 10:12:55
 * @modifier   : 2025-10-20 10:12:55
 */
final class LowCodeResidentService extends LowCodeBaseService
{
    /** 患者详情
     * @param string $userId
     *
     * @return array
     */
        public function basicInfo(string $userId = '')
        {
            //基本信息
            $info = LowCodeQueryEngineService::instance()
                                             ->autoClient()
                                             ->whereUserId($userId)
                                             ->setCache(30)
                                             ->getOnceResult();

            //人群分类信息
            $crowds = LowCodeQueryEngineService::instance()
                                               ->useTable('feature_user_detail')
                                               ->whereMixed([['user_id','=',$userId]])
                                               ->setCache(5)
                                               ->select(['user_id', 'group_id', 'group_name'])
                                               ->getAllResult();

            return compact('info','crowds');
        }
}
