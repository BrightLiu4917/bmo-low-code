<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Services\LowCode;


use App\Models\LowCode\Disease;
use Illuminate\Support\Facades\DB;
use BrightLiu\LowCode\Traits\CastDefaultFixHelper;
use GuzzleHttp\Exception\GuzzleException;
use App\Models\LowCode\DatabaseSource;
use BrightLiu\LowCode\Services\LowCodeBaseService;
use BrightLiu\LowCode\Models\Traits\Cacheable\CacheableModel;
use BrightLiu\LowCode\Enums\Model\DatabaseSource\SourceTypeEnum;
use Gupo\BetterLaravel\Exceptions\ServiceException;
use BrightLiu\LowCode\Services\LowCode\LowCodeDatabaseSourceService;

/*
 * 疾病编码
 */

final class LowCodeDiseaseService extends LowCodeBaseService
{
    use CacheableModel, CastDefaultFixHelper;

    /**
     * @param array $data
     *
     * @return Disease|null
     */
    public function create(array $data): Disease|null
    {
        $filterArgs = $this->fixInputDataByCasts($data, Disease::class);
        if (empty($filterArgs['extraction_pattern'])){
            $filterArgs['extraction_pattern'] = '/^慢病配药管理平台-(.*)$/';
        }
        $filterArgs['name'] = trim($filterArgs['name']);//避免项目操作有空格
        $filterArgs['code'] = trim($filterArgs['code']);//避免项目操作有空格

        $diseaseNames = array_values(array_filter(array_map(fn ($item,
        ) => sprintf(config('business.disease.role_tag_template',
                            '慢病配药管理平台-%s'
                     ), $item
        ), [$filterArgs['name']])));

        $syncCreateTag = (int)($data['sync_create_tag'] ?? 0);
        $data = DB::transaction(function()use($filterArgs,$diseaseNames,$syncCreateTag){
            //创建数据仓库
            if (!$this->syncCreateDataSource(diseaseCode:$filterArgs['code'],diseaseName:$filterArgs['name'])){
                throw new ServiceException('初始化数据仓库失败');
            }
           return Disease::query()->create($filterArgs);
        });
        return $data;
    }

    /**
     * @param int $id
     *
     * @return Disease|null
     * @throws ServiceException
     */
    public function show(int $id = 0): Disease|null
    {
        if (!$result = Disease::fetch($id)) {
            throw new ServiceException("ID:{$id}不存在");
        }
        return $result;
    }

    /**
     * @param int $id
     *
     * @return bool
     * @throws ServiceException
     */
    public function delete(int $id = 0): bool
    {
        if (!$result = Disease::fetch($id)) {
            throw new ServiceException("ID:{$id}不存在");
        }
        return $result->forceDelete();
    }

    /**
     * @param array|DiseaseEntity $data
     * @param int                 $id
     *
     * @return bool
     * @throws ServiceException
     */
    public function update(array $data, int $id = 0)
    {
        if (!$result = Disease::fetch($id)) {
            throw new ServiceException("ID:{$id}不存在");
        }
        $filterArgs = $this->fixInputDataByCasts($filterArgs, Disease::class);
        return $result->update($filterArgs);
    }

    public function syncCreateDataSource(string $diseaseCode = '', string $diseaseName = '')
    {
        //找到第一个数据仓库作为模板
        $rawDataSourceData = DatabaseSource::query()->where(['source_type'=>SourceTypeEnum::NO])->first();

        if (empty($rawDataSourceData)) {
            throw new ServiceException('通知 后端开发大佬 初始化第一个数仓数据源');
        }

        $LatestDataSourceData = array_merge(
            $rawDataSourceData->makeHidden(['id','code'])->toArray(),
                          [
                              //根据新的疾病编码名称等字段 覆盖旧的数据源字段然后新增到数据源表内
                              'disease_code' => $diseaseCode,
                              'name'         => $diseaseName,
                              'table'        => strtolower($diseaseCode)."_cmprhsv_tb",
                          ]
        );
        if (!DatabaseSource::query()->create($LatestDataSourceData)){
            throw new ServiceException('初始化数据仓库失败');
        }
       return true;
    }
}
