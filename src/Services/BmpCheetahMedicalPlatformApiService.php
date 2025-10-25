<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services;

use Illuminate\Support\Facades\Http;
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
     * @param array<int> $statisticsTypes 统计项目类型 0-患者任务状态统计，1-服务方任务状态统计，2-近N天新增患者统计，3-今日打卡人数统计
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
}
