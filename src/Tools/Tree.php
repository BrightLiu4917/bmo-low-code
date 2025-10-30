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
    }


    public static function buildRegionTree(array $regions, array $targetCodes,string $key = 'code',string $pKey = 'parent_code'): array
    {
        // 1. 构建索引：code => region
        $map = [];
        foreach ($regions as $r) {
            $map[$r[$key]] = $r + ['children' => []];
        }

        // 2. 找出所有涉及的 code（目标 + 祖先）
        $relatedCodes = [];

        $getAncestors = function ($code) use (&$map, &$relatedCodes, &$getAncestors,$pKey) {
            if (!isset($map[$code]) || in_array($code, $relatedCodes, true)) {
                return;
            }
            $relatedCodes[] = $code;
            $parent = $map[$code][$pKey];
            if ($parent && isset($map[$parent])) {
                $getAncestors($parent);
            }
        };

        foreach ($targetCodes as $code) {
            $getAncestors($code);
        }

        // 3. 只保留相关节点
        $filtered = array_filter($map, fn($r) => in_array($r[$key], $relatedCodes, true));

        // 4. 构建树
        $tree = [];
        foreach ($filtered as $code => &$node) {
            if ($node[$pKey] && isset($filtered[$node[$pKey]])) {
                $filtered[$node[$pKey]]['children'][] = &$node;
            } else {
                $tree[] = &$node;
            }
        }
        unset($node); // 断开引用

        return $tree;
    }

}
