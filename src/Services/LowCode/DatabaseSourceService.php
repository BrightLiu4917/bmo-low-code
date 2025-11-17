<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Services\LowCode;

use BrightLiu\LowCode\Traits\CastDefaultFixHelper;
use Illuminate\Support\Facades\Cache;
use BrightLiu\LowCode\Services\LowCodeBaseService;
use BrightLiu\LowCode\Models\Traits\Cacheable\CacheableModel;
use BrightLiu\LowCode\Models\DatabaseSource;
use Gupo\BetterLaravel\Exceptions\ServiceException;
use BrightLiu\LowCode\Models\Traits\DiseaseRelation;
use BrightLiu\LowCode\Core\DbConnectionManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * 数据库源
 */
final class DatabaseSourceService extends LowCodeBaseService
{
    use CacheableModel, DiseaseRelation,CastDefaultFixHelper;

    public function create(array $data): ?DatabaseSource
    {
        try {
            $filterArgs = $this->fixInputDataByCasts($data,DatabaseSource::class);
            $result = DatabaseSource::query()->create($filterArgs);
            if (!$result) {
                throw new ServiceException("创建失败");
            }
            return $result;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @param int $id
     *
     * @return DatabaseSource|null
     */
    public function show(int $id = 0): ?DatabaseSource
    {
        if ($id <= 0) {
            return null; // 防止无效id继续查询和缓存
        }

        return DatabaseSource::query()->select(
            ['id', 'disease_code', 'code', 'name', 'host', 'database',
             'source_type', 'table', 'port', 'options', 'updater_id',
             'username', 'password', 'created_at', 'updated_at', 'creator_id']
        )->with(
            [
//                'creator:id,realname',
//                'updater:id,realname',
                'disease:code,name']
        )->find($id);
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    public function delete(int $id = 0): bool
    {
        try {
            $result = DatabaseSource::query()->where('id',$id)->first();
            if (!$result) {
                throw new ServiceException("数据库源ID：{$id}不存在");
            }
            $result->delete();
        } catch (\Throwable $e) {
            return false;
        }
        return true;
    }

    /**
     * @param int                        $id
     *
     * @return DatabaseSource|null
     */
    public function update(int $id, array $data,): ?DatabaseSource {
        $result = DatabaseSource::fetch($id);
        if (!$result) {
            throw new ServiceException("数据库源{$id}不存在");
        }
        $filterArgs = $this->fixInputDataByCasts($data,DatabaseSource::class);
        if ($result->update($filterArgs)) {
            Cache::forget(
                DbConnectionManager::CONFIG_CACHE_KEY_PREFIX.$result['code']
            );
        }
        return $result->refresh();
    }


    /**
     * @param string $code
     *
     * @return mixed
     */
    public function fetchDataByCode(string $code = '')
    {
        return DatabaseSource::query()->where('code', $code)->select(
            ['name', 'host', 'database', 'table', 'port', 'options', 'username',
             'password']
        )->first();

    }

    public function getDataByDiseaseCode(string $diseaseCode = '')
    {
        return DatabaseSource::query()->where(
            'disease_code', $diseaseCode
        )->value('code');
    }
}
