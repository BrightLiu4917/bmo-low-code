<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\LowCode;

use BrightLiu\LowCode\Models\LowCodeCrowdLayer;
use BrightLiu\LowCode\Services\LowCodeBaseService;
use BrightLiu\LowCode\Tools\Clock;
use BrightLiu\LowCode\Traits\Context\WithDiseaseContext;
use BrightLiu\LowCode\Traits\Context\WithOrgContext;
use Gupo\BetterLaravel\Exceptions\ServiceException;
use Illuminate\Support\Facades\DB;

/**
 * 人群分层服务
 */
final class LowCodeCrowdLayerService extends LowCodeBaseService
{
    use WithDiseaseContext, WithOrgContext;

    /**
     * @throws ServiceException
     */
    public function save(string $moduleId, string $moduleType, array $items): bool
    {
        $formattedItems = collect($items)->map(fn ($item, $index) => [
            'disease_code' => $this->getDiseaseCode(),
            'org_code' => $this->getAffiliatedOrgCode(),
            'title' => $item['title'] ?? '',
            'module_id' => $moduleId,
            'module_type' => $moduleType,
            'crowd_id' => $item['crowd_id'] ?? '',
            'created_at' => Clock::now(),
            'weight' => 10000 - $index,
            'preset_filters' => json_encode([
                ['crowd_id', '=', $item['crowd_id'] ?? '']
            ])
        ]);

        if (
            $formattedItems
                ->groupBy(fn ($item) => "{$item['title']}")
                ->some(fn ($group) => count($group) > 1)
        ) {
            throw new ServiceException('标题重复');
        }

        DB::transaction(function () use ($formattedItems) {
            LowCodeCrowdLayer::query()
                ->where('disease_code', $this->getDiseaseCode())
                ->where('org_code', $this->getAffiliatedOrgCode())
                ->delete();

            LowCodeCrowdLayer::query()->insert($formattedItems->toArray());
        });

        return true;
    }
}
