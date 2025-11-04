<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Tools;

final class Tree
{

    /**
     * @param  array  $list
     * @param $pk
     * @param $pid
     * @param $children
     * @param $root
     *
     * @return array
     */

    public static function listToTree(array $list, $pk = 'code', $pid = 'p_code', $children = 'children', $root = '')
    {
        try {
            $tree = $refer = [];
            if (is_array($list)) {
                foreach ($list as $key => $value) {
                    $refer[$value[$pk]] =& $list[$key];
                }
                foreach ($list as $key => $value) {
                    $parentId = $value[$pid];
                    if ($root == $parentId) {
                        $tree[] =& $list[$key];
                    } else {
                        if (!empty($refer[$parentId])) {
                            $parent =& $refer[$parentId];
                            $parent[$children][] =& $list[$key];
                        }
                    }
                }
            }
            return $tree;
        }catch (\Exception $throwable){
        }
    }

    private static function buildCompleteTree(array $map, string $key = 'code', string $pKey = 'parent_code'): array
    {
        $tree = [];

        // 构建完整树形结构
        foreach ($map as $code => &$node) {
            if (!empty($node[$pKey]) && isset($map[$node[$pKey]])) {
                // 动态创建children字段（如果不存在）
                if (!isset($map[$node[$pKey]]['children'])) {
                    $map[$node[$pKey]]['children'] = [];
                }
                $map[$node[$pKey]]['children'][] = &$node;
            } else {
                $tree[] = &$node;
            }
        }
        unset($node);

        return $tree;
    }

    public static function buildRegionTree(array $regions, array $targetCodes, string $key = 'code', string $pKey = 'parent_code'): array
    {
        // 1. 构建索引：code => region
        $map = [];
        foreach ($regions as $r) {
            $map[$r[$key]] = $r ;
        }

        // 如果目标代码为空，构建完整树形结构
        if (empty($targetCodes)) {
            return self::buildCompleteTree($map, $key, $pKey);
        }

        // 2. 找出所有涉及的 code（上级 + 下级）
        $relatedCodes = [];

        // 向上查找祖先
        $getAncestors = function ($code) use (&$map, &$relatedCodes, &$getAncestors, $pKey) {
            if (!isset($map[$code]) || in_array($code, $relatedCodes, true)) {
                return;
            }
            $relatedCodes[] = $code;
            $parent = $map[$code][$pKey];
            if ($parent && isset($map[$parent])) {
                $getAncestors($parent);
            }
        };

        // 向下查找子孙
        $getDescendants = function ($code) use (&$map, &$relatedCodes, &$getDescendants, $key, $pKey) {
            foreach ($map as $r) {
                if ($r[$pKey] === $code && !in_array($r[$key], $relatedCodes, true)) {
                    $relatedCodes[] = $r[$key];
                    $getDescendants($r[$key]);
                }
            }
        };

        // 遍历目标节点，找上下级
        foreach ($targetCodes as $code) {
            $getAncestors($code);
            $getDescendants($code);
        }

        // 3. 过滤相关节点
        $filtered = array_filter($map, fn($r) => in_array($r[$key], $relatedCodes, true));

        // 4. 构建树
        $tree = [];
        foreach ($filtered as $code => &$node) {
            //            if ($node[$pKey] && isset($filtered[$node[$pKey]])) {
            //                $filtered[$node[$pKey]]['children'][] = &$node;
            //            } else {
            //                $tree[] = &$node;
            //            }
            if ($node[$pKey] && isset($filtered[$node[$pKey]])) {
                // 动态创建children字段（如果不存在）
                if (!isset($filtered[$node[$pKey]]['children'])) {
                    $filtered[$node[$pKey]]['children'] = [];
                }
                $filtered[$node[$pKey]]['children'][] = &$node;
            } else {
                $tree[] = &$node;
            }

        }
        unset($node); // 断开引用

        return $tree;
    }

}
