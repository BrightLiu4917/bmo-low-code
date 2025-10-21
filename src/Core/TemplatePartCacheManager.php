<?php

namespace BrightLiu\LowCode\Core;

use BrightLiu\LowCode\Enums\Foundation\Logger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\LowCode\LowCodeList;

/**
 * @Class
 * @Description:
 * @created    : 2025-05-26 19:44:55
 * @modifier   : 2025-05-26 19:44:55
 */
class TemplatePartCacheManager
{
    // 缓存时间（秒）
    protected static int $ttl = 86400;

    /**
     * 获取 LowCodeList 及其关联的 template parts 内容
     * 返回: [$list, $parts] 其中 $parts 是以 template_code 为键的 content 集合
     */
    public static function getListWithParts(string $code): array
    {
        try {
                // 每个 code 一个缓存（包含拼接后的组件）
                $result = Cache::remember(
                    "low_code_list_with_parts:{$code}", self::$ttl,
                    function() use ($code) {
                        // 获取列表主表配置
                        $list = LowCodeList::query()->where('code', $code)
                            ->select(
                                ['id', 'code', 'admin_name', 'admin_weight',
                                 'family_doctor_weight', 'mobile_doctor_weight',
                                 'append_field_json', 'remove_field_json',
                                 'append_column_json', 'remove_column_json',
                                 'append_filter_json', 'remove_filter_json',
                                 'append_button_json', 'remove_button_json',
                                 'append_top_button_json',
                                 'remove_top_button_json',
                                 'template_code_column', 'template_code_filter',
                                 'template_code_button',
                                 'template_code_top_button',]
                            )->first();

                        if (!$list) {
                            return null;
                        }
                        $templateCodes = collect(
                            ['column'     => $list->template_code_column,
                             'filter'     => $list->template_code_filter,
                             'button'     => $list->template_code_button,
                             'top_button' => $list->template_code_top_button,]
                        )->filter();

                        $parts = $templateCodes->mapWithKeys(
                            function(string $tplCode, string $key) use ($list) {
                                // 原始模板组件缓存（复用）
                                $rawKey = "template_parts:{$tplCode}";
                                $contentList = Cache::remember(
                                    $rawKey, self::$ttl,
                                    function() use ($tplCode) {
                                        return DB::table(
                                            'low_code_template_has_parts as t'
                                        )->join(
                                                'low_code_parts as p',
                                                't.part_code', '=', 'p.code'
                                            )->where(
                                                't.template_code', $tplCode
                                            )->orderBy('t.weight','asc')->pluck(
                                                'p.content'
                                            );
                                    }
                                );

                                $decodedList = $contentList->map(
                                    function($item) {
                                        $decoded = json_decode($item, true);
                                        return json_last_error()
                                        === JSON_ERROR_NONE
                                        && is_array($decoded) ? $decoded : [];
                                    }
                                );

                                // 拼接追加、移除配置
                                $removeKey = "remove_{$key}_json";
                                $appendKey = "append_{$key}_json";

                                return [$key => self::formatLowCodeConfig(
                                    templateConfigs: $decodedList->toArray(),
                                    appendConfigs: $list->$appendKey ?? [],
                                    removeConfigs: $list->$removeKey ?? []
                                )];
                            }
                        );

                        return ['code'                 => $code,
                                'admin_name'           => $list->admin_name,
                                'admin_weight'         => $list->admin_weight,
                                'family_doctor_weight' => $list->family_doctor_weight,
                                'mobile_doctor_weight' => $list->mobile_doctor_weight,
                                'pre_config'           => $parts->isEmpty()
                                    ? new \stdClass : $parts,];
                    }
                );
            return $result;
        } catch (\Throwable $e) {
            Logger::LOW_CODE_LIST->error(
                '获取多个列表及零件失败',
                ['codes' => $code, 'message' => $e->getMessage(),
                 'file'  => $e->getFile(), 'line' => $e->getLine(),]
            );
            return collect($code)->mapWithKeys(fn ($code) => [$code => null])
                ->toArray();
        }
    }

    /**
     * 清除指定 code 的缓存
     *
     * @param string $code
     *
     * @return void
     */
    public static function clearListCache(string $code): void
    {
        Cache::forget("low_code_list:{$code}");
        Cache::forget("low_code_list_with_parts:{$code}");

    }

    /**
     * 清除指定 template_code 的 part 缓存
     *
     * @param string $templateCode
     *
     * @return void
     */
    public static function clearTemplatePartsCache(string $templateCode): void
    {
        Cache::forget("template_parts:{$templateCode}");
    }

    /**
     * 格式化配置
     *
     * @param array|null $templateConfigs
     * @param array|null $appendConfigs
     * @param array|null $removeConfigs
     *
     * @return array|null
     */
    protected static function formatLowCodeConfig(?array $templateConfigs = [],
        ?array $appendConfigs = [], ?array $removeConfigs = [],
    ): array|null {
        try {
            if (empty($templateConfigs)) {
                return [];
            }
            //从模板内移除配置
            if (!empty($removeConfigs)) {
                $templateConfigs = self::removeByKey(
                    $templateConfigs,
                    collect($removeConfigs)->pluck('key')->toArray()
                );
            }

            //从模板内追加配置
            if (!empty($appendConfigs)) {
                $templateConfigs = self::insertAfter(
                    $templateConfigs, $appendConfigs
                );
            }

        } catch (\Throwable $exception) {

        }
        return $templateConfigs;
    }


    /**
     * 从数组中移除指定 key 的元素
     *
     * @param array  $data         原始数组
     * @param array  $keysToRemove 要移除的 key 列表
     * @param string $keyField     键字段名（默认是 key）
     *
     * @return array
     */
    public static function removeByKey(array $data, array $keysToRemove,
        string $keyField = 'key',
    ): array {
        return array_values(
            array_filter($data, function($item) use ($keysToRemove, $keyField) {
                return !in_array(
                    data_get($item, $keyField), $keysToRemove, true
                );
            })
        );
    }

    /**
     * @param array $base
     * @param array $inserts
     *
     * @return array
     */
    public static function insertAfter(array $base, array $inserts): array
    {
        // 收集 base 中已有的 key，方便快速判断
        $baseKeys = array_column($base, 'key');

        foreach ($inserts as $insertItem) {
            // 如果 insertItem 没有 key，跳过
            if (!isset($insertItem['key'])) {
                continue;
            }

            $insertKey = $insertItem['key'];

            // 如果 key 已存在于 base，则跳过
            if (in_array($insertKey, $baseKeys, true)) {
                continue;
            }

            $afterKey = $insertItem['insert_after_key'] ?? null;
            $inserted = false;

            if (!empty($afterKey)) {
                foreach ($base as $index => $item) {
                    if (($item['key'] ?? null) === $afterKey) {
                        array_splice($base, $index + 1, 0, [$insertItem]);
                        $inserted = true;
                        break;
                    }
                }
            }

            // 没指定或未找到 afterKey，追加到末尾
            if (!$inserted) {
                $base[] = $insertItem;
            }

            // 更新 key 列表，避免重复插入
            $baseKeys[] = $insertKey;
        }
        return $base;
    }
}
