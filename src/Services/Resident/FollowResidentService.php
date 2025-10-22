<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\Resident;

use BrightLiu\LowCode\Models\Resident\FollowResident;
use BrightLiu\LowCode\Traits\Context\WithContext;
use Gupo\BetterLaravel\Exceptions\ServiceException;
use Gupo\BetterLaravel\Service\BaseService;

/**
 * 关注居民相关
 */
class FollowResidentService extends BaseService
{
    use WithContext;

    /**
     * 获取关注中的信息
     */
    public function getFollowing(string $userId): ?FollowResident
    {
        return FollowResident::query()
            ->where('disease_code', $this->getDiseaseCode())
            ->where('admin_id', $this->getAdminId())
            ->where('resident_user_id', $userId)
            ->first();
    }

    /**
     * 关注
     *
     * @throws ServiceException
     */
    public function follow(string $userId): bool
    {
        if (empty($userId)) {
            return false;
        }

        if (!ResidentService::make()->exists($userId)) {
            throw new ServiceException('居民不存在');
        }

        FollowResident::query()->updateOrCreate(
            [
                'disease_code' => $this->getDiseaseCode(),
                'admin_id' => $this->getAdminId(),
                'resident_user_id' => $userId,
            ]
        );

        return true;
    }

    /**
     * 取消关注
     *
     * @throws ServiceException
     */
    public function unfollow(string $userId): bool
    {
        if (!ResidentService::make()->exists($userId)) {
            throw new ServiceException('居民不存在');
        }

        return (bool) FollowResident::query()
            ->where('disease_code', $this->getDiseaseCode())
            ->where('admin_id', $this->getAdminId())
            ->where('resident_user_id', $userId)
            ->delete();
    }
}
