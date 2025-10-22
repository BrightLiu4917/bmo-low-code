<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\Resident;

use BrightLiu\LowCode\Traits\Context\WithContext;
use BrightLiu\LowCode\Traits\Context\WithOrgContext;
use BrightLiu\LowCode\Traits\Context\WithAuthContext;
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
    use WithContext, WithAuthContext;
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


    public function manageResident(string $userId, array $attributes): void
    {
        if (empty($userId) || empty($attributes)) {
            return;
        }

        if (!$this->exists($userId)) {
            throw new ServiceException('居民不存在');
        }

        /**
         * 纳管状态           manage_status         int      // 状态标识（0:待纳管 1:已纳管 2:拒绝纳管 3:退出纳管）
         * 纳管机构编码       manage_org_code         string   // 机构唯一标识
         * 纳管机构名称       manage_org_name         string   // 机构全称
         * 主管医生编码       manage_doctor_code     string   // 医生唯一标识
         * 主管医生姓名       manage_doctor_name     string   // 医生姓名
         * 纳管生效时间       manage_start_at         time     // 纳管操作时间
         * 纳管团队编码       manage_team_code      string   // 团队唯一标识
         * 纳管团队名称       manage_team_name      string   // 团队全称
         * 纳管科室编码       manage_dept_code      string   // 科室唯一标识
         * 纳管科室名称       manage_dept_name      string   // 科室全称
         * 纳管终止时间       manage_end_at          time     // 取消纳管时间（含退出/出组/死亡）
         */
        $latestData['manage_status']      = 1;
        $latestData['manage_start_dt']    = date('Y-m-d H:i:s');
        $latestData['manage_org_code']    = $this->getOrgCode();
        $latestData['manage_end_at']      = null;
        $latestData['manage_doctor_code'] = auth()->user()->account ?? '';
        $latestData['manage_doctor_name'] = auth()->user()->name ?? '';

        BmpCheetahMedicalCrowdkitApiService::make()->updatePatientInfo(
            $userId,
            array_merge($attributes, $latestData)
        );
    }

    /**
     * @param string $userId
     * @param array  $attributes
     * @param bool   $isClearManageData
     *
     * @return void
     * @throws \Gupo\BetterLaravel\Exceptions\ServiceException
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function removeManageResident(string $userId, array $attributes,bool $isClearManageData =  true): void
    {
        if (empty($userId) || empty($attributes)) {
            return;
        }
        if (!$this->exists($userId)) {
            throw new ServiceException('居民不存在');
        }
        $latestData['manage_status'] = 3;
        $latestData['manage_end_at'] = date('Y-m-d H:i:s');
        if ($isClearManageData) {
            $latestData['manage_start_dt']    = null;
            $latestData['manage_org_code']    = '';
            $latestData['manage_org_name']    = '';
            $latestData['manage_doctor_code'] = '';
            $latestData['manage_doctor_name'] = '';
            $latestData['manage_team_code']   = '';
            $latestData['manage_team_name']   = '';
            $latestData['manage_dept_name']   = '';
            $latestData['manage_dept_code']   = '';
        }

        BmpCheetahMedicalCrowdkitApiService::make()->updatePatientInfo(
            $userId,
            array_merge($attributes, $latestData)
        );
    }
}
