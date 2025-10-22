<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\Resident;

use BrightLiu\LowCode\Traits\Context\WithContext;
use Gupo\BetterLaravel\Exceptions\ServiceException;
use Gupo\BetterLaravel\Service\BaseService;

/**
 * 测试居民相关
 */
class TestingResidentService extends BaseService
{
    use WithContext;

    /**
     * 标记为测试
     */
    public function maskTesting(string $userId): bool
    {
        if (empty($userId)) {
            return false;
        }

        if (!ResidentService::make()->exists($userId)) {
            throw new ServiceException('居民不存在');
        }

        ResidentService::make()->updateInfo($userId, ['is_testing' => 1]);

        return true;
    }

    /**
     * 取消标记为测试
     */
    public function unmaskTesting(string $userId): bool
    {
        if (!ResidentService::make()->exists($userId)) {
            throw new ServiceException('居民不存在');
        }

        ResidentService::make()->updateInfo($userId, ['is_testing' => 0]);

        return true;
    }
}
