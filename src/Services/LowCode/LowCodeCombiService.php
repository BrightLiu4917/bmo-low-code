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

                $item['filters'] = array_merge($item['filters'] ?? [], [['crowd_id', '=', $exploded['crowd_id']]]);

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
            $exploded = explode('#', $code);

            $mapping[] = ['code' => $exploded[0] ?? '', 'crowd_id' => $exploded[1] ?? ''];
        }

        return $mapping;
    }

    public function combiListCode(string $code, string $crowdId): string
    {
        return "{$code}#{$crowdId}";
    }
}
