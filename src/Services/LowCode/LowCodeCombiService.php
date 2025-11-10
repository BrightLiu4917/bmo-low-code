<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Services\LowCode;

use Gupo\BetterLaravel\Service\BaseService;

final class LowCodeCombiService extends BaseService
{
    /**
     * 解析code(code中可能携带中台的人群ID)
     * @param array $inputArgs
     */
    public function handleInputArgs(array $inputArgs): array
    {
        return collect($inputArgs)
            ->map(function ($item) {
                if (empty($item['code']) || !str_contains($item['code'], '#')) {
                    return $item;
                }

                [0 => $exploded] = $this->resolveCombiCodeMapping($item['code']);

                return array_merge($item, [
                    'code'    => $exploded['code'],
                    'filters' => array_merge(
                        $item['filters'] ?? [],
                        [['crowd_id', '=', $exploded['crowd_id']]]
                    ),
                ]);
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
