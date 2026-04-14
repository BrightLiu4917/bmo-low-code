<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\LowCode;

use BrightLiu\LowCode\Enums\Foundation\Logger;
use BrightLiu\LowCode\Models\LowCodeCrowdLayer;
use BrightLiu\LowCode\Models\LowCodePersonalizeModule;
use BrightLiu\LowCode\Services\LowCodeBaseService;
use BrightLiu\LowCode\Traits\Context\WithDiseaseContext;
use BrightLiu\LowCode\Traits\Context\WithOrgContext;
use Gupo\BetterLaravel\Exceptions\ServiceException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * 个性化模块相关
 */
final class LowCodePersonalizeModuleService extends LowCodeBaseService
{
    use WithDiseaseContext, WithOrgContext;

    public function save(array $items, string $defaultModuleType = ''): bool
    {
        $formattedItems = collect($items)->map(fn ($item, $index) => [
            'disease_code' => $this->getDiseaseCode(),
            'org_code' => $this->getAffiliatedOrgCode(),
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

        $historyModules = null;
        try {
            $historyModules = LowCodePersonalizeModule::query()
                ->where('org_code', $this->getAffiliatedOrgCode())
                ->where('disease_code', $this->getDiseaseCode())
                ->get(['id', 'metadata']);
        } catch (Throwable $e) {
            // 兼容低版本数据库可能缺失personalize_module表的情况，避免因迁移未完成导致的功能不可用
            Logger::LARAVEL->error('Failed to fetch history personalize modules', [
                'org_code' => $this->getAffiliatedOrgCode(),
                'disease_code' => $this->getDiseaseCode(),
                'error' => $e->getMessage(),
            ]);
        }

        DB::transaction(function () use ($formattedItems, $historyModules) {
            LowCodePersonalizeModule::query()
                ->where('org_code', $this->getAffiliatedOrgCode())
                ->where('disease_code', $this->getDiseaseCode())
                ->delete();

            LowCodePersonalizeModule::query()->insert($formattedItems->toArray());

            if (!empty($historyModules)) {
                $this->reuseCrowdLayersByMetadataPath($historyModules);
            }
        });

        return true;
    }

    private function reuseCrowdLayersByMetadataPath(Collection $historyModules): void
    {
        $historyPathToModuleId = $historyModules->mapWithKeys(function (LowCodePersonalizeModule $module) {
            $path = $this->extractMetadataPath($module->metadata);

            if ('' === $path || empty($module->id)) {
                return [];
            }

            return [$path => (string) $module->id];
        });

        $newModules = LowCodePersonalizeModule::query()
            ->where('org_code', $this->getAffiliatedOrgCode())
            ->where('disease_code', $this->getDiseaseCode())
            ->get(['id', 'metadata']);

        $newPathToModuleId = $newModules->mapWithKeys(function (LowCodePersonalizeModule $module) {
            $path = $this->extractMetadataPath($module->metadata);
            if ('' === $path || empty($module->id)) {
                return [];
            }

            return [$path => (string) $module->id];
        });

        $moduleIdMap = [];
        foreach ($historyPathToModuleId as $path => $oldModuleId) {
            $newModuleId = (string) $newPathToModuleId->get($path, '');
            if ('' === $newModuleId || $newModuleId === $oldModuleId) {
                continue;
            }

            $moduleIdMap[$oldModuleId] = $newModuleId;
        }

        foreach ($moduleIdMap as $oldModuleId => $newModuleId) {
            LowCodeCrowdLayer::query()
                ->where('disease_code', $this->getDiseaseCode())
                ->where('org_code', $this->getAffiliatedOrgCode())
                ->where('module_type', 'personalize_module')
                ->where('module_id', $oldModuleId)
                ->update(['module_id' => $newModuleId]);
        }

        $staleModuleIds = $historyPathToModuleId
            ->filter(fn (string $moduleId, string $path) => !$newPathToModuleId->has($path))
            ->values()
            ->all();

        if (!empty($staleModuleIds)) {
            LowCodeCrowdLayer::query()
                ->where('disease_code', $this->getDiseaseCode())
                ->where('org_code', $this->getAffiliatedOrgCode())
                ->where('module_type', 'personalize_module')
                ->whereIn('module_id', $staleModuleIds)
                ->delete();
        }
    }

    /**
     * @param array<string, mixed>|string|null $metadata
     */
    private function extractMetadataPath(array|string|null $metadata): string
    {
        if (is_array($metadata)) {
            return (string) ($metadata['path'] ?? '');
        }

        if (is_string($metadata) && '' !== $metadata) {
            $decoded = json_decode($metadata, true);
            if (is_array($decoded)) {
                return (string) ($decoded['path'] ?? '');
            }
        }

        return '';
    }

    public function getModuleCrowdId(int $id): string
    {
        return (string) LowCodePersonalizeModule::query()->where('id', $id)->value('module_id');
    }
}
