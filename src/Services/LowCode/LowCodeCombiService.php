<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\LowCode;

use Gupo\BetterLaravel\Service\BaseService;

final class LowCodeCombiService extends BaseService
{
    /**
     * 解析code(code中可能携带中台的人群ID)
     */
    public function handleInputArgs(array $inputArgs): array
    {
        return collect($inputArgs)->map(function ($item) {
            if (!empty($item['code']) && str_contains($item['code'], '#')) {
                [0 => $exploded] = $this->resolveCombiCodeMapping($item['code']);

                $item['original_code'] = $item['code'];
                $item['code'] = $exploded['code'];

                $moduleId = (int) ($exploded['personalize_module_id'] ?? 0);
                $originalCrowdIds = $exploded['crowd_ids'] ?? [];
                if ($moduleId > 0) {
                    $crowdIds = LowCodePersonalizeModuleService::make()->getModuleCrowdIds($moduleId);
                } else {
                    $crowdIds = $originalCrowdIds;
                }

                if (count($crowdIds) > 1) {
                    // 多个人群不进入 crowd_id 条件，避免被 mergeCrowdIdFilters 与分层/filters 合成一条 in
                    $item['list_crowd_ids'] = $crowdIds;
                } elseif (count($crowdIds) === 1) {
                    $item['filters'] = array_merge($item['filters'] ?? [], [['crowd_id', '=', $crowdIds[0]]]);
                } elseif ($moduleId > 0) {
                    // 菜单存在，但当前病种/场景一个都匹配不上
                    $item['filters'] = array_merge($item['filters'] ?? [], [['crowd_id', '=', '0']]);
                }

                // 过滤掉无效条件
                if (!empty($item['filters']) && is_array($item['filters'])) {
                    $item['filters'] = array_values(
                        array_filter(
                            $item['filters'],
                            fn ($itemFilter) => !(
                                is_array($itemFilter)
                                && count($itemFilter) >= 3
                                && in_array((string) $itemFilter[1], ['like', '=', '<>', 'in'])
                                && ('' === $itemFilter[2] || null === $itemFilter[2])
                            )
                        )
                    );
                }
            }

            // 合并code中携带的人群ID条件(合并为in操作)，避免出现crowd_id条件覆盖
            if (!empty($item['filters']) && is_array($item['filters'])) {
                $item['filters'] = $this->mergeCrowdIdFilters($item['filters']);
            }

            return $item;
        })->toArray();
    }

    public function mergeCrowdIdFilters(array $filters): array
    {
        $crowdIdFilters = array_values(
            array_filter(
                $filters,
                fn ($itemFilter) => is_array($itemFilter)
                    && count($itemFilter) >= 3
                    && ($itemFilter[0] ?? '') === 'crowd_id'
            )
        );

        if (count($crowdIdFilters) > 1) {
            $crowdIds = [];

            foreach ($crowdIdFilters as $crowdIdFilter) {
                $operator = mb_strtolower((string) ($crowdIdFilter[1] ?? ''));
                $value = $crowdIdFilter[2] ?? null;

                if ('in' === $operator && is_array($value)) {
                    foreach ($value as $inValue) {
                        if ('' !== $inValue && null !== $inValue) {
                            $crowdIds[] = $inValue;
                        }
                    }
                } elseif ('=' === $operator && '' !== $value && null !== $value) {
                    $crowdIds[] = $value;
                }
            }

            $crowdIds = array_values(array_unique($crowdIds));

            $filters = array_values(
                array_filter(
                    $filters,
                    fn ($itemFilter) => !(
                        is_array($itemFilter)
                        && count($itemFilter) >= 1
                        && ($itemFilter[0] ?? '') === 'crowd_id'
                    )
                )
            );

            if (!empty($crowdIds)) {
                $filters[] = ['crowd_id', 'in', $crowdIds];
            }
        }

        return $filters;
    }

    public function resolveListCode(string|array $codes): string|array
    {
        $isOnce = !is_array($codes);

        $codes = (array) $codes;

        $codes = array_map(fn ($item) => explode('#', $item)[0], $codes);

        return $isOnce ? end($codes) ?? '' : $codes;
    }

    public function resolveCombiCodeMapping(string|array $codes): array
    {
        $codes = (array) $codes;

        $mapping = [];

        foreach ($codes as $code) {
            $exploded = explode('#', (string) $code, 3);
            $crowdIds = array_values(array_unique(array_filter(
                array_map(static fn ($id) => trim((string) $id), explode(',', (string) ($exploded[1] ?? ''))),
                static fn (string $id) => '' !== $id
            )));

            $mapping[] = [
                'code' => $exploded[0] ?? '',
                'crowd_id' => $crowdIds[0] ?? '',
                'crowd_ids' => $crowdIds,
                'personalize_module_id' => trim((string) ($exploded[2] ?? '')),
            ];
        }

        return $mapping;
    }

    public function combiListCode(string $code, string $crowdId, string $moduleId = ''): string
    {
        $combined = "{$code}#{$crowdId}";

        return '' !== $moduleId ? "{$combined}#{$moduleId}" : $combined;
    }
}
