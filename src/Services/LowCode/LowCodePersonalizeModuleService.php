<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\LowCode;

use App\Traits\Context\WithDiseaseContext;
use App\Models\LowCode\LowCodePersonalizeModule;
use BrightLiu\LowCode\Services\LowCodeBaseService;
use Gupo\BetterLaravel\Exceptions\ServiceException;
use Gupo\BetterLaravel\Service\BaseService;
use Illuminate\Support\Facades\DB;

/**
 * 个性化模块相关
 */
final class LowCodePersonalizeModuleService extends LowCodeBaseService
{
//    use WithDiseaseContext;

    public function save(array $items, string $defaultModuleType = ''): bool
    {
        $formattedItems = collect($items)->map(fn ($item, $index) => [
//            'disease_code' => $this->getDiseaseCode(),
            'title' => $item['title'] ?? '',
            'metadata' => json_encode($item['metadata'] ?? []),
            'module_id' => $item['module_id'] ?? '',
            'module_type' => $item['module_type'] ?? $defaultModuleType,
            'created_at' => date('Y-m-d H:i:s'),
            'weight' => 10000 - $index,
        ]);

        if (
            $formattedItems
                ->groupBy(fn ($item) => "{$item['module_type']}:{$item['title']}")
                ->some(fn ($group) => count($group) > 1)
        ) {
            throw new ServiceException('标题重复');
        }

        DB::transaction(function () use ($formattedItems) {
            LowCodePersonalizeModule::query()->where('disease_code', $this->getDiseaseCode())->delete();

            LowCodePersonalizeModule::query()->insert($formattedItems->toArray());
        });

        return true;
    }
}
