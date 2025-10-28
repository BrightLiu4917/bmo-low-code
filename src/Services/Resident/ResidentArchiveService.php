<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\Resident;

use BrightLiu\LowCode\Support\CrowdConnection;
use Gupo\BetterLaravel\Exceptions\ServiceException;
use Gupo\BetterLaravel\Service\BaseService;
use Illuminate\Support\Collection;
use BrightLiu\LowCode\Services\CrowdKitService;

/**
 * 居民档案相关
 */
class ResidentArchiveService extends BaseService
{
    /**
     * 获取基础信息
     *
     * @return array{info:array,following:?FollowResident,crowd_info:Collection}
     *
     * @throws ServiceException
     */
    public function getBasicInfo(string $empi): array
    {
        // 基本信息
        $info = ResidentService::make()->getInfo($empi);

        if (empty($info)) {
            throw new ServiceException('居民不存在');
        }

        // 关注状态
        $following = FollowResidentService::make()->getFollowing($empi);

        // 人群分类
        $crowdInfo = (array) CrowdConnection::table('feature_user_detail')
            ->where('empi', $info['empi'])
            ->get(['group_id'])
            ->each(function ($item) {
                $item->group_name =  (string) CrowdKitService::instance()->resolveGroupName(intval($item->group_id ?? ''));

//                $item->offsetSet('group_name', (string) CrowdKitService::instance()->resolveGroupName(intval($item->group_id ?? '')));
            })
            ->toArray();

        return [
            'info' => $info,
            'following' => $following,
            'crowd_info' => $crowdInfo,
        ];
    }

    /**
     * 获取信息
     *
     * @throws ServiceException
     */
    public function getInfo(string $empi): array
    {
        $info = ResidentService::make()->getInfo($empi);

        if (empty($info)) {
            throw new ServiceException('居民不存在');
        }

        return (array) $info;
    }

    /**
     * 更新信息
     *
     * @throws ServiceException
     */
    public function updateInfo(string $empi, array $attributes): void
    {
        // TODO: 待完善
        $guarded = ['id_crd_no', 'empi', 'is_deleted', 'gmt_created', 'gmt_modified'];

        $attributes = array_diff_key($attributes, array_flip($guarded));

        ResidentService::make()->updateInfo($empi, $attributes);
    }
}
