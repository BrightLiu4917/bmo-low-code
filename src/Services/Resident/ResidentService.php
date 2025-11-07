<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\Resident;

use BrightLiu\LowCode\Traits\Context\WithContext;
use BrightLiu\LowCode\Traits\Context\WithOrgContext;
use BrightLiu\LowCode\Traits\Context\WithAuthContext;
use BrightLiu\LowCode\Services\BmpCheetahMedicalPlatformApiService;
use BrightLiu\LowCode\Entities\Business\Resident\ResidentBasicInfoEntity;
use BrightLiu\LowCode\Services\BmpCheetahMedicalCrowdkitApiService;
use BrightLiu\LowCode\Support\CrowdConnection;
use Gupo\BetterLaravel\Exceptions\ServiceException;
use Gupo\BetterLaravel\Service\BaseService;
use Illuminate\Support\Facades\DB;
use BrightLiu\LowCode\Tools\BetterArr;
use Closure;
use BrightLiu\LowCode\Events\Resident\ResidentInfoUpdated;

/**
 * 居民相关
 */
class ResidentService extends BaseService
{
    use WithContext, WithAuthContext;
    /**
     * 判断居民是否存在
     */
    public function exists(string $empi): bool
    {
        return !empty(CrowdConnection::query()->where('empi', $empi)->exists());
    }

    /**
     * 获取居民基本信息
     *
     * @throws ServiceException
     */
    public function getBasicInfo(string $empi): ?ResidentBasicInfoEntity
    {
        if (empty($empi)) {
            return null;
        }

        return ResidentBasicInfoEntity::make((array) $this->getInfo($empi));
    }

    /**
     * 获取居民信息
     *
     * @throws ServiceException
     */
    public function getInfo(string $empi, array $columns = ['t2.*', 't1.*']): ?array
    {
        if (empty($empi)) {
            return null;
        }

        return $this->first(fn ($query) => $query->where('t1.empi', $empi), $columns);
    }

    /**
     * 根据身份评点与获取居民信息
     *
     * @throws ServiceException
     */
    public function getInfoByCardNo(string $idCardNo, array $columns = ['t2.*', 't1.*']): ?array
    {
        if (empty($idCardNo)) {
            return null;
        }

        return $this->first(fn ($query) => $query->where('t1.id_crd_no', $idCardNo), $columns);
    }

    /**
     * 基本信息查询
     */
    public function first(\Closure $query, array $columns = ['t2.*', 't1.*']): ?array
    {
        $psnTable = config('low-code.bmo-baseline.database.crowd-psn-wdth-table');

        // TODO: log
        if (empty($psnTable)) {
            return null;
        }

        if (in_array('empi', $columns)) {
            $columns = array_map(fn ($c) => $c === 'empi' ? 't1.empi' : $c, $columns);
        }

        $connection = CrowdConnection::connection();

        $sceneTable = $connection->getConfig('table');

        $result = $connection
            ->table($psnTable, 't1')
            ->leftJoin("{$sceneTable} as t2", 't1.empi', '=', 't2.empi')
            ->where($query)
            ->first($columns);

        return !empty($result) ? BetterArr::toArray($result) : null;
    }

    /**
     * 更新居民基本信息
     *
     * @throws ServiceException
     */
    public function updateBasicInfo(string $empi, ResidentBasicInfoEntity $basicInfo): void
    {
        $this->updateInfo($empi, $basicInfo->only([
            'rsdnt_nm', 'slf_tel_no', 'gdr_cd', 'bth_dt',
        ]));
    }

    /**
     * 更新居民信息
     *
     * @throws ServiceException
     */
    public function updateInfo(string $empi, array $attributes): void
    {
        if (empty($empi) || empty($attributes)) {
            return;
        }

        if (!$this->exists($empi)) {
            throw new ServiceException('居民不存在');
        }

        BmpCheetahMedicalCrowdkitApiService::make()->updatePatientInfo(
            $empi,
            $attributes
        );

        silence_event(new ResidentInfoUpdated($empi, $attributes));
    }


    public function manageResident(string $empi, array $attributes): void
    {
        if (empty($empi) || empty($attributes)) {
            return;
        }

        if (!$this->exists($empi)) {
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
            $empi,
            array_merge($attributes, $latestData)
        );
    }

    /**
     * @param  string  $empi
     * @param  string  $patientName
     * @param  int  $projectId
     * @param  string  $baseDate
     * @param  string  $arcCode
     * @param  string  $areaCode
     * @param  int  $splitFlag
     *
     * @return int
     */
    public function createUserManagePlanTask(
        string $empi = '',
        string $patientName = '',
        int $projectId = 0,
        string $baseDate = '',
        string $arcCode = '',
        string $areaCode = '',
        string $orgCode = '',
        int $adminId = 0,
        string $adminName = '',
        int $splitFlag = 0,
    ):int
    {
       return BmpCheetahMedicalPlatformApiService::instance()->createUserManagePlanTask(
           empi: $empi,
           patientName: $patientName,
           projectId: $projectId,
           baseDate: $baseDate,
           arcCode: $arcCode,
           areaCode: $areaCode,
           splitFlag: $splitFlag,
           orgCode:$orgCode,
           adminId: $adminId,
            adminName: $adminName,
        );
    }

    public function stopUserManagePlanTask(
        string $empi = '',
        string $sceneCode = '',

    ):bool
    {
        return BmpCheetahMedicalPlatformApiService::instance()->stopUserManagePlanTask(
            empi: $empi,
            sceneCode: $sceneCode,

        );
    }

    /**
     * @param string $empi
     * @param array  $attributes
     * @param bool   $isClearManageData
     *
     * @return void
     * @throws \Gupo\BetterLaravel\Exceptions\ServiceException
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function removeManageResident(string $empi = '', array $attributes  = [],bool $isClearManageData =  true): void
    {
        if (empty($empi)) {
            return;
        }
        if (!$this->exists($empi)) {
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
            $empi,
            array_merge($attributes, $latestData)
        );
    }

    /**
     * 创建患者
     * @param  array  $args
     *
     * @return array
     */
    public function create (string $idCardNo = '' ,array $args = []):array
    {
       return BmpCheetahMedicalPlatformApiService::instance()->createPatient(idCardNo:$idCardNo,args: $args);
    }
}
