<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\Resident;

use BrightLiu\LowCode\Entities\Business\Resident\ResidentBasicInfoEntity;
use BrightLiu\LowCode\Services\BmpCheetahMedicalCrowdkitApiService;
use BrightLiu\LowCode\Support\CrowdConnection;
use Gupo\BetterLaravel\Exceptions\ServiceException;
use Gupo\BetterLaravel\Service\BaseService;

/**
 * 居民相关
 */
class ResidentService extends BaseService
{
    /**
     * 判断居民是否存在
     */
    public function exists(string $userId): bool
    {
        return !empty(CrowdConnection::query()->where('user_id', $userId)->exists());
    }

    /**
     * 获取居民基本信息
     *
     * @throws ServiceException
     */
    public function getBasicInfo(string $userId): ResidentBasicInfoEntity
    {
        if (empty($userId)) {
            throw new ServiceException('参数错误');
        }

        return ResidentBasicInfoEntity::make((array) $this->getInfo($userId));
    }

    /**
     * 获取居民信息
     *
     * @throws ServiceException
     */
    public function getInfo(string $userId, array $columns = ['*']): array
    {
        $info = CrowdConnection::query()->where('user_id', $userId)->first($columns);

        if (empty($info)) {
            throw new ServiceException('居民不存在');
        }

        return (array) $info;
    }

    /**
     * 更新居民基本信息
     *
     * @throws ServiceException
     */
    public function updateBasicInfo(string $userId, ResidentBasicInfoEntity $basicInfo): void
    {
        $this->updateInfo($userId, $basicInfo->only([
            'rsdnt_nm', 'slf_tel_no', 'gdr_cd', 'bth_dt',
        ]));
    }

    /**
     * 更新居民信息
     *
     * @throws ServiceException
     */
    public function updateInfo(string $userId, array $attributes): void
    {
        if (empty($userId) || empty($attributes)) {
            return;
        }

        if (!$this->exists($userId)) {
            throw new ServiceException('居民不存在');
        }

        BmpCheetahMedicalCrowdkitApiService::make()->updatePatientInfo(
            $userId,
            $attributes
        );
    }
}
