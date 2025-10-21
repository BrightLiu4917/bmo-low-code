<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Tools;


final class BetterArr
{
    /**
     * 与array_merge_recursive行为类似，但仅在值为数组时才递归合并。
     *
     * @param array<array> $arrays
     */
    public static function merge(...$arrays): array
    {
        $result = [];

        foreach ($arrays as $array) {
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $prevValue = $result[$key] ?? [];

                    /* @phpstan-ignore-next-line */
                    if ('array' !== gettype($prevValue)) {
                        $prevValue = [];
                    }

                    $value = self::merge($prevValue, $value);
                }

                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * 获取值
     * PS: 多个时按优先级获取
     */
    public static function get(mixed $data, mixed $keys, mixed $default = null): mixed
    {
        $keys = (array) $keys;

        return collect($data)->first(fn ($v, $k) => in_array($k, $keys), $default);
    }

    public static function toArray(mixed $data): array
    {
        try {
            return (array) json_decode(json_encode($data), true);
        } catch (\Throwable $e) {
            return (array) $data;
        }
    }
}
