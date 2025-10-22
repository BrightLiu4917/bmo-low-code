<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\Resident;

use BrightLiu\LowCode\Support\CrowdConnection;
use Gupo\BetterLaravel\Exceptions\ServiceException;
use Gupo\BetterLaravel\Service\BaseService;
use Illuminate\Support\Collection;

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
    public function getBasicInfo(string $userId): array
    {
        // 基本信息
        $info = ResidentService::make()->getInfo($userId);

        // 关注状态
        $following = FollowResidentService::make()->getFollowing($userId);

        // 人群分类
        $crowdInfo = (array) CrowdConnection::table('feature_user_detail')
            ->where('user_id', $info['user_id'])
            ->get(['group_id', 'group_name'])
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
    public function getInfo(string $userId): array
    {
        $info = ResidentService::make()->getInfo($userId);

        return (array) $info;
    }

    /**
     * 更新信息
     *
     * @throws ServiceException
     */
    public function updateInfo(string $userId, array $attributes): void
    {
        // TODO: 待完善
        $guarded = ['id_crd_no', 'user_id', 'is_deleted', 'gmt_created', 'gmt_modified'];

        $attributes = array_diff_key($attributes, array_flip($guarded));

        ResidentService::make()->updateInfo($userId, $attributes);
    }
}
