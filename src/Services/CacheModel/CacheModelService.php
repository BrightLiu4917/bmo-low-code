<?php

namespace BrightLiu\LowCode\Services\CacheModel;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use BrightLiu\LowCode\Services\LowCodeBaseService;

class CacheModelService extends LowCodeBaseService
{
    /**
     * 清除与指定模型相关的缓存。
     *
     * 根据配置决定是否清除整个标签下的缓存，或者仅清除与模型ID和代码相关的缓存。
     *
     * @param Model $model 需要清除缓存的模型实例
     * @return void
     */
    public static function clearModelCache(Model $model): void
    {
        $tag = $model::getTag();

        // 如果配置为在更新时清除整个标签下的缓存，则执行清除操作并返回
        if (config('cache-model.flush_tag_on_update')) {
            Cache::tags($tag)->flush();
            return;
        }

        // 如果启用了ID和代码缓存，则清除与模型ID和代码相关的缓存
        if (config('cache-model.enable_id_code_cache')) {
            Cache::tags($tag)->forget($model->getCacheKeyById());

            if (!empty($model->code)) {
                Cache::tags($tag)->forget($model->getCacheKeyByCode());
            }
        }
    }

    /**
     * 缓存指定键的数据，并在缓存未命中时执行回调函数获取数据。
     *
     * 使用指定的标签和生存时间（TTL）缓存数据。如果缓存中不存在该键，则执行回调函数获取数据并缓存。
     *
     * @param string $key 缓存键
     * @param string $tag 缓存标签
     * @param Closure $callback 缓存未命中时执行的回调函数，用于获取数据
     * @return mixed 缓存的数据或回调函数返回的数据
     */
    public static function remember(string $key, string $tag, Closure $callback)
    {
        $ttl = config('cache-model.ttl', 600);
        return Cache::tags($tag)->remember($key, $ttl, $callback);
    }
}
