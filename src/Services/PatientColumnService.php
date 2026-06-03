<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services;

use Illuminate\Support\Facades\Cache;

/**
 * 患者字段列数据中央服务
 *
 * 提供字段枚举映射和元信息的批量获取，支持双级缓存：
 * - 请求级静态缓存（同请求内共享，防循环请求外部接口）
 * - 配置级缓存（跨请求复用，默认关闭，通过 BLC_PATIENT_COLUMN_CACHE_ENABLED 开启）
 *
 * @Class
 * @Description: 患者字段枚举&元信息服务
 */
class PatientColumnService extends LowCodeBaseService
{
    /** 请求级枚举缓存 */
    protected static array $enumMappingCache = [];

    /** 请求级元信息缓存 */
    protected static array $fieldMetaCache = [];

    /**
     * 批量获取字段枚举映射
     *
     * @param array $fieldKeys 字段 key 列表
     * @return array 按 field_key 索引的枚举映射
     *               ['gdr_cd' => ['1' => '男', '2' => '女'], ...]
     */
    public function getEnumMapping(array $fieldKeys): array
    {
        if (empty($fieldKeys)) {
            return [];
        }

        $sorted = $fieldKeys;
        sort($sorted);
        $cacheKey = md5(serialize($sorted));

        // 1. 请求级缓存（静态变量，同请求内共享）
        if (array_key_exists($cacheKey, static::$enumMappingCache)) {
            return static::$enumMappingCache[$cacheKey];
        }

        // 2. 配置级缓存（跨请求，可选）
        if (config('low-code.patient-column.cache-enabled')) {
            $cached = Cache::get("low_code_pc_enum_{$cacheKey}");
            if ($cached !== null) {
                static::$enumMappingCache[$cacheKey] = $cached;
                return $cached;
            }
        }

        // 3. 调用远程 API
        $result = BmpCheetahMedicalCrowdkitApiService::make()
            ->getPatientFieldEnumList($fieldKeys);

        // 4. 写入请求级缓存
        static::$enumMappingCache[$cacheKey] = $result;

        // 5. 写入配置级缓存
        if (config('low-code.patient-column.cache-enabled')) {
            Cache::put(
                "low_code_pc_enum_{$cacheKey}",
                $result,
                now()->addSeconds((int) config('low-code.patient-column.cache-ttl', 600))
            );
        }

        return $result;
    }

    /**
     * 批量获取字段元信息
     *
     * @param array $fieldKeys 字段 key 列表
     * @return array 按 field_key 索引的元信息
     *               ['gdr_cd' => ['data_type' => 'INT', ...], ...]
     */
    public function getFieldMeta(array $fieldKeys): array
    {
        if (empty($fieldKeys)) {
            return [];
        }

        $sorted = $fieldKeys;
        sort($sorted);
        $cacheKey = md5(serialize($sorted));

        // 1. 请求级缓存（静态变量，同请求内共享）
        if (array_key_exists($cacheKey, static::$fieldMetaCache)) {
            return static::$fieldMetaCache[$cacheKey];
        }

        // 2. 配置级缓存（跨请求，可选）
        if (config('low-code.patient-column.cache-enabled')) {
            $cached = Cache::get("low_code_pc_meta_{$cacheKey}");
            if ($cached !== null) {
                static::$fieldMetaCache[$cacheKey] = $cached;
                return $cached;
            }
        }

        // 3. 调用远程 API
        $result = BmpCheetahMedicalCrowdkitApiService::make()
            ->getPatientFieldMetaList($fieldKeys);

        // 4. 写入请求级缓存
        static::$fieldMetaCache[$cacheKey] = $result;

        // 5. 写入配置级缓存
        if (config('low-code.patient-column.cache-enabled')) {
            Cache::put(
                "low_code_pc_meta_{$cacheKey}",
                $result,
                now()->addSeconds((int) config('low-code.patient-column.cache-ttl', 600))
            );
        }

        return $result;
    }

    /**
     * 统一入口：同时获取枚举映射 + 字段元信息
     *
     * 在一次调用中同时获取两种数据，减少外部 API 调用次数。
     *
     * @param array $fieldKeys 字段 key 列表
     * @return array ['_enum_mapping' => [...], '_field_meta' => [...]]
     */
    public function buildConversionContext(array $fieldKeys): array
    {
        if (empty($fieldKeys)) {
            return [
                '_enum_mapping' => [],
                '_field_meta' => [],
            ];
        }

        return [
            '_enum_mapping' => $this->getEnumMapping($fieldKeys),
            '_field_meta' => $this->getFieldMeta($fieldKeys),
        ];
    }
}
