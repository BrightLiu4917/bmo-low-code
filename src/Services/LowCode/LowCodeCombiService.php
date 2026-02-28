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
        return collect($inputArgs)
            ->map(function ($item) {
                if (empty($item['code']) || !str_contains($item['code'], '#')) {
                    return $item;
                }

                [0 => $exploded] = $this->resolveCombiCodeMapping($item['code']);

                $item['code'] = $exploded['code'];

                $latestCrowdIdIndex = array_search('crowd_id', array_column($item['filters'] ?? [], 0));

                if (false === $latestCrowdIdIndex) {
                    $item['filters'] = array_merge($item['filters'] ?? [], [['crowd_id', '=', $exploded['crowd_id']]]);
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
                                && ($itemFilter[2] === '' ||  $itemFilter[2] === null)
                            )
                        )
                    );
                }

                return $item;
            })
            ->toArray();
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
