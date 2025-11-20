<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services;

use Illuminate\Support\Facades\Http;
use BrightLiu\LowCode\Enums\Foundation\Logger;
use BrightLiu\LowCode\Traits\Context\WithAuthContext;
use BrightLiu\LowCode\Traits\Context\WithContext;

/**
 * 业务平台-服务平台模块
 */
final class BmpCheetahMedicalPlatformApiService extends LowCodeBaseService
{
    use WithContext, WithAuthContext;

    /**
     * 声明 base_uri
     */
    protected function baseUriVia(): string
    {
        return config('business.bmo-service.bmp_cheetah_medical_platform.uri', '');
    }

    /**
     * 获取人群分类
     */
    public function getCrowds(): array
    {

        $data = Http::asJson()->post($this->baseUriVia() . 'innerapi/userGroup/page', [
            'org_code' => $this->getOrgCode(),
            'sys_code' => $this->getSystemCode(),
            'disease_code' => $this->getDiseaseCode(),
            'scene_code' => $this->getSceneCode(),
            'tenant_id' => $this->getTenantId(),
        ])->json();
        return $data['data']['results'] ??[];
    }

    /**
     * 获取患者统计数据
     *
     * @param array<int> $statisticsTypes 统计项目类型
     *     0-患者任务状态统计，1-服务方任务状态统计，2-近N天新增患者统计，3-今日打卡人数统计
     */
    public function getPatientStatisticsData(array $statisticsTypes = [0, 3]): array
    {
        $data = Http::asJson()->timeout(10)->post($this->baseUriVia() . 'innerapi/stat/queryStatData', [
            'stat_types' => $statisticsTypes,
            'org_code' => $this->getOrgCode(),
            'sys_code' => $this->getSystemCode(),
            'disease_code' => $this->getDiseaseCode(),
            'scene_code' => $this->getSceneCode(),
            'tenant_id' => $this->getTenantId(),
        ])->json();
        return $data['data'] ??[];

    }

    /**
     * 获取患者预警统计数据
     */
    public function getPatientWarningStatisticsData(): int
    {
        $data = Http::asJson()->timeout(3)->post($this->baseUriVia() . 'innerapi/stat/queryWarnCount', [
            'org_code' => $this->getOrgCode(),
            'sys_code' => $this->getSystemCode(),
            'disease_code' => $this->getDiseaseCode(),
            'scene_code' => $this->getSceneCode(),
            'tenant_id' => $this->getTenantId(),
        ])->json();
        return $data['data'] ?? 0;
    }

    /**
     * @param  string  $empi
     * @param  string  $sceneCode
     *
     * @return mixed
     */
    public function stopUserManagePlanTask(
        string $empi = '',
        string $sceneCode = '',
    ): bool {
        $data = Http::asJson()->timeout(3)->post(
            $this->baseUriVia().'/innerapi/task/completedAll',
            [
                "patient_id" => $empi,
                "scene_code" => $sceneCode ?: $this->getSceneCode(),
            ]
        )->json();
        return !empty($data['data']) ? true : false;
    }

    /**
     * 创建管理方案
     * @param  string  $empi
     * @param  string  $patientName
     * @param  int  $projectId
     * @param  string  $baseDate
     * @param  string  $arcCode
     * @param  string  $areaCode
     * @param  string  $orgCode
     * @param  int  $splitFlag
     * @param  int  $adminId
     * @param  string  $adminName
     *
     * @return string
     */
    public function createUserManagePlanTask(
        string $empi = '',
        string $patientName = '',
        int $projectId = 0,
        string $baseDate = '',
        string $arcCode = '',
        string $areaCode = '',
        string $orgCode = '',
        int $splitFlag = 0,
        int $adminId = 0,
        string $adminName = '',
    )
    {
        $data = Http::asJson()->timeout(3)->post($this->baseUriVia() . '/innerapi/patient/manager',
            [
                "arc_code"     => $arcCode ?: $this->getArcCode(),
                "area_code"    => $areaCode,
                "base_date"    => $baseDate,
                "disease_code" => $this->getDiseaseCode(),
                "org_code" => $orgCode ?:
                    $this->getOrgCode() ?: $this->getOrgId(),
                "patient_id"   => $empi,
                "patient_name" => $patientName,
                "project_id"   => $projectId,
                "scene_code"   => $this->getSceneCode(),
                "split_flag"   => $splitFlag,
                "sys_code"     => $this->getSystemCode(),
                "tenant_id"    => $this->getTenantId(),
                "admin_id"     => $adminId,
                "admin_name"   => $adminName,
        ]
        )->json();
        Logger::BMP_CHEETAH_MEDICAL_DEBUG->debug(
            '创建管理方案-debug',
            [
                'input_args' => [
                    "arc_code"     => $arcCode ?: $this->getArcCode(),
                    "area_code"    => $areaCode,
                    "base_date"    => $baseDate,
                    "disease_code" => $this->getDiseaseCode(),
                    "org_code"     => $orgCode ?: $this->getOrgId(),
                    "patient_id"   => $empi,
                    "patient_name" => $patientName,
                    "project_id"   => $projectId,
                    "scene_code"   => $this->getSceneCode(),
                    "split_flag"   => $splitFlag,
                    "sys_code"     => $this->getSystemCode(),
                    "tenant_id"    => $this->getTenantId(),
                    "admin_id"     => $adminId,
                    "admin_name"   => $adminName,
                ],
                'respose'=>$data
            ]
        );
        $result = data_get($data,'data',[]);
        if (empty($result)){
            return [null,null,$data['message']];
        }
        return [$result,'',''];
    }
}
