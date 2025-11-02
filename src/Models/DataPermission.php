<?php

namespace BrightLiu\LowCode\Models;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\SoftDeletes;
use BrightLiu\LowCode\Enums\Foundation\Logger;
use BrightLiu\LowCode\Models\Traits\ModelFetch;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use BrightLiu\LowCode\Models\Traits\Cacheable\NewEloquentBuilder;
use BrightLiu\LowCode\Enums\Model\LowCode\LowCodeList\ListTypeEnum;
use BrightLiu\LowCode\Models\Traits\OrgDiseaseRelation;
use BrightLiu\LowCode\Models\Traits\OrgRelation;
use BrightLiu\LowCode\Models\Traits\Cacheable\CacheableModel;
use BrightLiu\LowCode\Models\Traits\UniqueCodeRelation;


/**
 * @Class
 * @Description:
 * @created    : 2025-10-01 11:51:47
 * @modifier   : 2025-10-01 11:51:47
 */
class DataPermission extends LowCodeBaseModel
{
    const CACHE_KEY_PREFIX = 'data-permission:';
    const CACHE_TTL = 3600;
    use
        ModelFetch, //        NewEloquentBuilder,
        SoftDeletes, UniqueCodeRelation, OrgDiseaseRelation, OrgRelation, CacheableModel;

    public static function getAllData()
    {
        try {

            return Cache::remember(
                key:DataPermission::CACHE_KEY_PREFIX,
                ttl:DataPermission::CACHE_TTL,
                callback:function ()  {
                    return DataPermission::query()->get();
                }
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {

        } catch (\Exception $e) {
            // 记录异常日志，便于问题排查
            Logger::DATA_PERMISSION_ERROR->error('DataPermission query failed', [
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
        }
        return null;
    }

}
