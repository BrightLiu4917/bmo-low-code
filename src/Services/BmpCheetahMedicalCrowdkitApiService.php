<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use BrightLiu\LowCode\Traits\Context\WithContext;
use BrightLiu\LowCode\Traits\Context\WithAuthContext;

/**
 * @Class
 * @Description:
 * @created    : 2025-10-22 15:07:39
 * @modifier   : 2025-10-22 15:07:39
 */
final class BmpCheetahMedicalCrowdkitApiService extends LowCodeBaseService
{
    use WithContext, WithAuthContext;

    /**
     * 声明 base_uri
     */
    protected function baseUriVia(): string
    {
        return config('business.bmo-service.bmp_cheetah_medical_crowd_kit.uri', '');
    }

    /**
     * 获取专病的人员宽表
     */
    public function getPatientCrowdInfo(int $orgId = 0): ?array
    {
        $data = Http::asJson()
            ->retry(3)
            ->timeout(15)
            ->get($this->baseUriVia().'innerapi/get_patient_crowd_info',[
                'org_code' => $this->getOrgCode(),
                'sys_code' => $this->getSystemCode(),
                'disease_code' => $this->getDiseaseCode(),
                'scene_code' => $this->getSceneCode()
            ])
            
            ->json();
        return $data['data'] ?? [];
    }


    /**
     * 获取人群分类
     */
    public function getCrowds(int $selectType = 1): array
    {
        $data = Http::asJson()->post($this->baseUriVia() . 'innerapi/userGroup/page', [
            'select_type' => $selectType,
            'org_code' => $this->getOrgCode(),
            'sys_code' => $this->getSystemCode(),
            'disease_code' => $this->getDiseaseCode(),
            'scene_code' => $this->getSceneCode(),
            'tenant_id' => $this->getTenantId(),
        ])->json();
        return $data['data']['results'] ??[];
    }

    /**
     * 获取专病的人员宽表
     */
    public function getPatientCrowdColGroup(): ?array
    {
        $data =  Http::asJson()
                     ->retry(3)
                     ->timeout(15)
                     ->get($this->baseUriVia().'innerapi/get_patient_crowd_col_group',[
                         'org_code' => $this->getOrgCode(),
                         'sys_code' => $this->getSystemCode(),
                         'disease_code' => $this->getDiseaseCode(),
                         'scene_code' => $this->getSceneCode()
                     ])
                     
                     ->json();
        return $data['data'] ?? [];
    }

    /**
     * 创建患者
     */
    public function createPatients(array $patients): void
    {
        if (empty($patients)) {
            return;
        }

        Http::asJson()
            ->retry(3)
            ->timeout(15)
            ->post($this->baseUriVia().'innerapi/get_patient_crowd_col_group',[
                'data_source' => 1,
                'org_code' => $this->getOrgCode(),
                'sys_code' => $this->getSystemCode(),
                'disease_code' => $this->getDiseaseCode(),
                'scene_code' => $this->getSceneCode()
            ])
            
            ->json();
    }

    /**
     * @param string $empi
     * @param array  $attributes
     *
     * @return void
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function updatePatientInfo(string $empi, array $attributes): void
    {
        if (empty($attributes)) {
            return;
        }

        Http::asJson()
            ->retry(3)
            ->timeout(15)
            ->post($this->baseUriVia().'innerapi/personal-archive/create',[
                'empi' => $empi,
                'col_values' => array_values(Arr::map(
                    $attributes,
                    fn ($value, $key) => ['col_name' => $key, 'col_value' => $value]
                )),
                'data_source' => 1,
                'org_code' => $this->getOrgCode(),
                'sys_code' => $this->getSystemCode(),
                'disease_code' => $this->getDiseaseCode(),
                'scene_code' => $this->getSceneCode()
            ])
            
            ->json();
    }

    /**
     * 获取居民可选指标项
     */
    public function getMetricOptional(): array
    {
        $data = Http::asJson()
                   ->retry(3)
                   ->timeout(15)
                   ->get($this->baseUriVia().'innerapi/personal-archive/field',[
                       'org_code' => $this->getOrgCode(),
                       'sys_code' => $this->getSystemCode(),
                       'disease_code' => $this->getDiseaseCode(),
                       'scene_code' => $this->getSceneCode()
                   ])
                   
                   ->json();
        return $data['data'] ?? [];
    }
}
