<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services\LowCode;

use BrightLiu\LowCode\Enums\Foundation\Logger;
use BrightLiu\LowCode\Models\LowCodeCrowdLayer;
use BrightLiu\LowCode\Models\LowCodePersonalizeModule;
use BrightLiu\LowCode\Services\BmpCheetahMedicalPlatformApiService;
use BrightLiu\LowCode\Services\LowCodeBaseService;
use BrightLiu\LowCode\Support\CrowdConnection;
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
        $normalizedItems = collect($items)->map(function ($item) use ($defaultModuleType) {
            $moduleIds = LowCodePersonalizeModule::normalizeCrowdIds((array) ($item['module_ids'] ?? []));
            if ($moduleIds === [] && '' !== trim((string) ($item['module_id'] ?? ''))) {
                $moduleIds = [trim((string) $item['module_id'])];
            }

            return [
                'title' => $item['title'] ?? '',
                'metadata' => $item['metadata'] ?? [],
                'module_ids' => $moduleIds,
                'module_type' => $item['module_type'] ?? $defaultModuleType,
            ];
        });

        $allCrowdIds = $normalizedItems
            ->filter(fn (array $item) => 'crowd_patients' === $item['module_type'])
            ->flatMap(fn (array $item) => $item['module_ids'])
            ->unique()
            ->values()
            ->all();
        $userGroups = $this->fetchUserGroupsByIds($allCrowdIds);

        $formattedItems = $normalizedItems->map(function ($item, $index) use ($userGroups) {
            $moduleIds = $item['module_ids'];
            $moduleMeta = 'crowd_patients' === $item['module_type']
                ? $this->buildModuleMeta($moduleIds, $userGroups)
                : [];

            return [
                'disease_code' => $this->getDiseaseCode(),
                'scene_code' => $this->getSceneCode(),
                'org_code' => $this->getAffiliatedOrgCode(),
                'title' => $item['title'],
                'metadata' => json_encode($item['metadata'] ?? []),
                'module_id' => $moduleIds[0] ?? '',
                'module_ids' => json_encode($moduleIds, JSON_UNESCAPED_UNICODE),
                'module_meta' => json_encode($moduleMeta, JSON_UNESCAPED_UNICODE),
                'module_type' => $item['module_type'],
                'created_at' => date('Y-m-d H:i:s'),
                'weight' => 10000 - $index,
            ];
        });

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
                ->where('scene_code', $this->getSceneCode())
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
                ->where('scene_code', $this->getSceneCode())
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
            ->where('scene_code', $this->getSceneCode())
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
                ->where('scene_code', $this->getSceneCode())
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
                ->where('scene_code', $this->getSceneCode())
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

    /**
     * @return array<int, string>
     */
    public function getModuleCrowdIds(int $id): array
    {
        $module = LowCodePersonalizeModule::query()
            ->where('id', $id)
            ->first(['module_id', 'module_ids', 'module_meta']);

        return $module instanceof LowCodePersonalizeModule ? $this->resolveCrowdIdsForContext($module) : [];
    }

    /**
     * 按请求头病种/场景从 module_meta 取人群 ID；旧数据无 meta 时回退全部 ID。
     *
     * @return array<int, string>
     */
    public function resolveCrowdIdsForContext(
        LowCodePersonalizeModule $module,
        ?string $diseaseCode = null,
        ?string $sceneCode = null
    ): array {
        $meta = $this->normalizeModuleMeta($module->module_meta ?? []);
        if ($meta === []) {
            return $module->resolveCrowdIds();
        }

        $diseaseCode ??= $this->getDiseaseCode();
        $sceneCode ??= $this->getSceneCode();

        $ids = [];
        foreach ($meta as $item) {
            $id = trim((string) ($item['id'] ?? ''));
            if ('' === $id) {
                continue;
            }
            if (trim((string) ($item['disease_code'] ?? '')) !== (string) $diseaseCode) {
                continue;
            }
            if (trim((string) ($item['scene_code'] ?? '')) !== (string) $sceneCode) {
                continue;
            }
            $ids[] = $id;
        }

        return LowCodePersonalizeModule::normalizeCrowdIds($ids);
    }

    /**
     * @return array<int, array{disease_code: string, scene_code: string, scene_name: string, metadata: array}>
     */
    public function getRelatedScenes(LowCodePersonalizeModule $module): array
    {
        $items = $this->uniqueScenes($module);
        if ($items === []) {
            $items = $this->fallbackScenes($module);
        }

        $bindScenes = BmpCheetahMedicalPlatformApiService::instance()->getChronicBindSceneList();
        $order = [];
        $sceneMap = [];
        foreach ($bindScenes as $index => $scene) {
            $order[$scene['scene_code']] = $index;
            $sceneMap[$scene['scene_code']] = $scene;
        }

        usort($items, static function (array $a, array $b) use ($order): int {
            $indexA = $order[$a['scene_code']] ?? PHP_INT_MAX;
            $indexB = $order[$b['scene_code']] ?? PHP_INT_MAX;

            return $indexA <=> $indexB;
        });

        foreach ($items as &$item) {
            $bind = $sceneMap[$item['scene_code']] ?? [];
            $item['disease_code'] = $item['disease_code'] ?: ($bind['disease_code'] ?? '');
            $item['scene_name'] = $bind['scene_name'] ?? '';
            $item['metadata'] = $bind['metadata'] ?? [];
        }
        unset($item);

        return array_values($items);
    }

    /**
     * @param array<int, string> $ids
     * @return array<string, object|array>
     */
    private function fetchUserGroupsByIds(array $ids): array
    {
        $ids = LowCodePersonalizeModule::normalizeCrowdIds($ids);
        if ($ids === []) {
            return [];
        }

        try {
            $userGroupTable = config('low-code.bmo-baseline.database.crowd-group-table', 'user_group');

            return CrowdConnection::connection()
                ->table($userGroupTable)
                ->whereIn('id', $ids)
                ->where('is_deleted', 0)
                ->get(['id', 'disease_code', 'scene_code'])
                ->keyBy(static fn ($row) => (string) (is_array($row) ? ($row['id'] ?? '') : ($row->id ?? '')))
                ->all();
        } catch (Throwable $e) {
            Logger::LARAVEL->error('Failed to fetch user_group for personalize module_meta', [
                'org_code' => $this->getAffiliatedOrgCode(),
                'ids' => $ids,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * @param array<int, string> $moduleIds
     * @param array<string, object|array> $userGroups
     * @return array<int, array{id: string, disease_code: string, scene_code: string}>
     */
    private function buildModuleMeta(array $moduleIds, array $userGroups): array
    {
        $meta = [];
        foreach ($moduleIds as $id) {
            $row = $userGroups[(string) $id] ?? null;
            if (null === $row) {
                continue;
            }

            $meta[] = [
                'id' => (string) $this->userGroupValue($row, 'id'),
                'disease_code' => trim((string) $this->userGroupValue($row, 'disease_code')),
                'scene_code' => trim((string) $this->userGroupValue($row, 'scene_code')),
            ];
        }

        return $meta;
    }

    /**
     * @return array<int, array{scene_code: string}>
     */
    private function fallbackScenes(LowCodePersonalizeModule $module): array
    {
        $crowdIds = $module->resolveCrowdIds();
        $userGroups = $this->fetchUserGroupsByIds($crowdIds);
        $items = [];
        $seen = [];
        foreach ($crowdIds as $id) {
            $row = $userGroups[(string) $id] ?? null;
            if (null === $row) {
                continue;
            }

            $diseaseCode = trim((string) $this->userGroupValue($row, 'disease_code'));
            $sceneCode = trim((string) $this->userGroupValue($row, 'scene_code'));
            if ('' === $sceneCode || isset($seen[$sceneCode])) {
                continue;
            }
            $seen[$sceneCode] = true;
            $items[] = [
                'disease_code' => $diseaseCode,
                'scene_code' => $sceneCode,
            ];
        }

        if ($items !== []) {
            return $items;
        }

        $sceneCode = trim((string) ($module->scene_code ?: $this->getSceneCode()));
        if ('' === $sceneCode) {
            return [];
        }

        return [[
            'disease_code' => trim((string) ($module->disease_code ?: $this->getDiseaseCode())),
            'scene_code' => $sceneCode,
        ]];
    }

    private function userGroupValue(object|array $row, string $key): mixed
    {
        if (is_array($row)) {
            return $row[$key] ?? '';
        }

        return $row->{$key} ?? '';
    }

    /**
     * @return array<int, array{scene_code: string}>
     */
    private function uniqueScenes(LowCodePersonalizeModule $module): array
    {
        $items = [];
        $seen = [];
        foreach ($this->normalizeModuleMeta($module->module_meta ?? []) as $item) {
            $sceneCode = trim((string) ($item['scene_code'] ?? ''));
            if ('' === $sceneCode || isset($seen[$sceneCode])) {
                continue;
            }
            $seen[$sceneCode] = true;
            $items[] = [
                'disease_code' => trim((string) ($item['disease_code'] ?? '')),
                'scene_code' => $sceneCode,
            ];
        }

        return $items;
    }

    /**
     * @param array<string, mixed>|array<int, mixed>|string|null $meta
     * @return array<int, array<string, mixed>>
     */
    private function normalizeModuleMeta(array|string|null $meta): array
    {
        if (is_string($meta) && '' !== $meta) {
            $decoded = json_decode($meta, true);
            $meta = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($meta)) {
            return [];
        }

        return array_values(array_filter($meta, 'is_array'));
    }
}
