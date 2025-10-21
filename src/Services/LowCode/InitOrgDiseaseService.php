<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\LowCode;
use BrightLiu\LowCode\Enums\Foundation\Logger;
use BrightLiu\LowCode\Services\LowCodeBaseService;
use BrightLiu\LowCode\Enums\Model\DatabaseSource\SourceTypeEnum;
use App\Models\LowCode\DatabaseSource;
use App\Models\LowCode\LowCodeList;
use App\Models\LowCode\LowCodePart;
use App\Models\LowCode\LowCodeTemplate;
use App\Models\LowCode\LowCodeTemplateHasPart;
use App\Services\Api\Bmp\BmpCheetahMedicalCrowdkitApiService;
use BrightLiu\LowCode\Services\LowCode\LowCodeDatabaseSourceService;
use BrightLiu\LowCode\Tools\Uuid;
use BrightLiu\LowCode\Traits\Context\WithContext;
use Gupo\BetterLaravel\Exceptions\ServiceException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 初始化机构病种
 */
final class InitOrgDiseaseService extends LowCodeBaseService
{
    use WithContext;

    /**
     * @param string $dataTableName 数仓表名
     * @param array $params 其他参数
     * @param bool $force 是否强制初始化
     */
    public function handle(string $dataTableName = '', array $params = [], bool $force = false): bool
    {
        // 根据 病种&机构 从crowdkit服务中获取
        if (empty($dataTableName)) {
            $dataTableName = $this->fetchDiseaseDataTableName();
        }

        if (!$force) {
            if (DatabaseSource::query()->where('disease_code', $this->getDiseaseCode())->exists()) {
                throw new ServiceException('该机构病种已初始化过');
            }
        }

        DB::transaction(function () use ($dataTableName) {
            // 前置清理
            $this->clean();

            // 初始化: 数据源
            $dataSource = $this->initDataSource($dataTableName);

            if (empty($dataSource)) {
                throw new ServiceException('数据源初始化异常');
            }

            // low-code 初始化
            $this->initLowCodeConfig($dataSource);
        });

        return true;
    }

    /**
     * 获取病种的数仓表名
     * PS: 当前病种在crowkit服务中对应的表名
     *
     * @throws ServiceException
     */
    public function fetchDiseaseDataTableName(): string
    {
        try {
            $data = BmpCheetahMedicalCrowdkitApiService::instance()->getPatientCrowdInfo(1);

            return $data['db_name'];
        } catch (\Throwable $e) {
            throw new ServiceException('获取人员信息表失败失败');
        }
    }

    /**
     * 前置清理
     */
    protected function clean(): void
    {
        if (empty($diseaseCode = $this->getDiseaseCode())) {
            return;
        }

        DatabaseSource::query()->where('disease_code', $diseaseCode)->delete();

        $templateCodes = LowCodeTemplate::query()->where('disease_code', $diseaseCode)->pluck('code')->toArray();

        if (!empty($templateCodes)) {
            $partCodes = LowCodeTemplateHasPart::query()->whereIn('template_code', $templateCodes)->pluck('part_code')->toArray();

            if (!empty($partCodes)) {
                LowCodePart::query()->whereIn('code', $partCodes)->delete();
            }

            LowCodeTemplateHasPart::query()->whereIn('template_code', $templateCodes)->delete();
            LowCodeTemplate::query()->whereIn('code', $templateCodes)->delete();
        }

        LowCodeList::query()->where('disease_code', $diseaseCode)->delete();
    }

    /**
     * 初始化: 数据源
     */
    protected function initDataSource(string $dataTableName): ?DatabaseSource
    {
        $dataWarehouseConfig = (array) config('business.medical-platform.data_warehouse.default', []);

        $data =  [];

        $data['disease_code'] = $this->getDiseaseCode();
        $data['name'] = $this->getDiseaseCode();
        $data['host'] = $dataWarehouseConfig['host'] ?? '';
        $data['database'] = $dataWarehouseConfig['database'] ?? '';
        $data['table'] = $dataTableName;
        $data['port'] = $dataWarehouseConfig['port'] ?? 3306;
        $data['username'] = $dataWarehouseConfig['username'] ?? '';
        $data['password'] = $dataWarehouseConfig['password'] ?? '';
        $data['options'] = $dataWarehouseConfig['options'] ?? [];
        $data['source_type'] = SourceTypeEnum::NO;

        return LowCodeDatabaseSourceService::instance()->create($data);
    }

    /**
     * 初始化: 低代码配置
     */
    protected function initLowCodeConfig(DatabaseSource $dataSource, string $scene = 'normal'): void
    {
        $initTemplates = $this->loadTemplates();

        if (empty($sceneTemplates = ($initTemplates[$scene] ?? ''))) {
            throw new ServiceException('场景初始化模板不存在');
        }

        $listData = [];
        foreach ($sceneTemplates as $sceneTemplate) {
            $sceneTemplateMapping = $this->initTemplates($sceneTemplate['templates'] ?? []);

            foreach ($sceneTemplate['list'] ?? [] as $item) {
                if (!empty($item['templates'])) {
                    $templateMapping = array_merge(
                        $sceneTemplateMapping->toArray(),
                        $this->initTemplates($item['templates'])->toArray()
                    );

                    unset($item['templates']);
                } else {
                    $templateMapping = $sceneTemplateMapping;
                }

                $listData[] = [
                    ...$item,
                    'code' => Uuid::generate(),
                    'disease_code' => $this->getDiseaseCode(),
                    'org_code' => $this->getOrgCode(),
//                    'creator_id' => $this->getAdminId(),
//                    'updater_id' => $this->getAdminId(),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'template_code_filter' => $templateMapping['filter']['code'] ?? '',
                    'template_code_column' => $templateMapping['column']['code'] ?? '',
                    'template_code_button' => $templateMapping['button']['code'] ?? '',
                    'template_code_top_button' => $templateMapping['top_button']['code'] ?? '',
                    'route_group' => json_encode($item['route_group'] ?? [], JSON_UNESCAPED_UNICODE),
                    'preset_condition_json' => json_encode($item['preset_condition_json'] ?? [], JSON_UNESCAPED_UNICODE),
                    'default_order_by_json' => json_encode($item['default_order_by_json'] ?? [], JSON_UNESCAPED_UNICODE),
                ];
            }
        }

        if (!empty($listData)) {
            LowCodeList::query()->insert($listData);
        }
    }

//    protected function initTemplates(array $templates): Collection
//    {
//        return collect($templates)->map(function ($templateItem) {
//            // 初始化template
//            $listTemplate = LowCodeTemplate::query()->create($templateItem);
//
//            // 预置parts公共信息
//            $partCommonInfo = [
//                'org_code' => $this->getOrgCode(),
//                'part_type' => 1,
//                'content_type' => $templateItem['content_type'],
////                'creator_id' => $this->getAdminId(),
////                'updater_id' => $this->getAdminId(),
//                'created_at' => date('Y-m-d H:i:s'),
//                'updated_at' => date('Y-m-d H:i:s'),
//            ];
//
//            $parts = collect($templateItem['parts'] ?? [])
//                ->map(fn ($item, $index) => [
//                    ...$item,
//                    ...$partCommonInfo,
//                    'content' => json_encode($item['content'] ?? [], JSON_UNESCAPED_UNICODE),
//                    'code' => Uuid::generate(),
//                    'weight' => $index,
//                ]);
//
//            // 初始化parts
//            LowCodePart::query()->insert($parts->toArray());
//
//            // 维护template与part的关联关系
//            LowCodeTemplateHasPart::query()->insert(
//                $parts->map(fn ($item, $index) => [
//                    'part_code' => $item['code'],
//                    'template_code' => $listTemplate['code'],
//                    'weight' => $index,
//                    'created_at' => date('Y-m-d H:i:s'),
//                    'updated_at' => date('Y-m-d H:i:s'),
//                ])->reverse()->toArray()
//            );
//
//            return $listTemplate;
//        });
//    }


    protected function initTemplates(array $templates)
    {
        try {
            return collect($templates)->map(function ($templateItem) {
                // 确保所有数组字段都转换为 JSON 字符串
                $templateData = [
                    'name' => $templateItem['name'] ?? null,
                    'description' => $templateItem['description'] ?? null,
                    'template_type' => $templateItem['template_type'] ?? null,
                    'content_type' => $templateItem['content_type'] ?? null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                // 初始化template
                $listTemplate = LowCodeTemplate::query()->create($templateData);

                // 预置parts公共信息
                $partCommonInfo = [
                    'org_code' => $this->getOrgCode(),
                    'part_type' => 1,
                    'content_type' => $templateItem['content_type'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                $parts = collect($templateItem['parts'] ?? [])
                    ->map(function ($item, $index) use ($partCommonInfo) {
                        return [
                            'name' => $item['name'] ?? null,
                            'description' => $item['description'] ?? null,
                            ...$partCommonInfo,
                            'content' => json_encode($item['content'] ?? [], JSON_UNESCAPED_UNICODE),
                            'code' => Uuid::generate(),
                            'weight' => $index,
                        ];
                    });

                // 初始化parts
                LowCodePart::query()->insert($parts->toArray());

                // 维护template与part的关联关系
                $templateHasParts = $parts->map(function ($item, $index) use ($listTemplate) {
                    return [
                        'part_code' => $item['code'],
                        'template_code' => $listTemplate['code'],
                        'weight' => $index,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                })->toArray();

                LowCodeTemplateHasPart::query()->insert($templateHasParts);

                return $listTemplate;
            });
        } catch (\Throwable $e) {
            // 更好的错误处理
            Logger::LOW_CODE_LIST->error('初始化模板失败', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new ServiceException('初始化模板失败: '.$e->getMessage());
        }
    }

    protected function loadTemplates(): array
    {
        try {
            return json_decode(file_get_contents(storage_path('templates.json')), true);
        } catch (\Throwable $e) {
            // TODO: ...
        }

        return [];
    }
}
