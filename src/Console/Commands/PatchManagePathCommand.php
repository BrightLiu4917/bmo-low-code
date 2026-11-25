<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Console\Commands;

use BrightLiu\LowCode\Enums\Foundation\Cacheable;
use BrightLiu\LowCode\Models\LowCodeList;
use BrightLiu\LowCode\Models\LowCodePersonalizeModule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * 按初始化模板回填状态 tab 的 query_scope，并给未关联管理方案的菜单补方案编号。
 */
final class PatchManagePathCommand extends Command
{
    protected $signature = 'low-code:patch-manage-path
        {--dry-run : 仅显示将要更新的数据，不写库}';

    protected $description = '回填状态tab查询范围，并给未关联管理方案的菜单补方案编号';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->patchListQueryScope($dryRun);
        $this->patchEmptySubModuleIds($dryRun);

        $this->info($dryRun ? '干运行结束，未写库' : '修补完成');

        return self::SUCCESS;
    }

    private function patchListQueryScope(bool $dryRun): void
    {
        $updated = 0;
        foreach ($this->queryScopeMapping() as $adminName => $queryScope) {
            $query = LowCodeList::query()
                ->where('list_type', '<>', 9)
                ->where('admin_name', $adminName)
                ->where(function ($builder) use ($queryScope) {
                    $builder->where('query_scope', '<>', $queryScope)
                        ->orWhereNull('query_scope');
                });

            $count = (clone $query)->count();
            if ($count < 1) {
                continue;
            }

            $updated += $count;
            $this->line(sprintf('状态tab [%s] => %s : %d', $adminName, $queryScope, $count));
            if (!$dryRun) {
                $codes = (clone $query)->pluck('code');
                $query->update(['query_scope' => $queryScope]);
                foreach ($codes as $code) {
                    Cacheable::LOW_CODE_LIST_QUERY_SCOPE->delete((string) $code);
                }
            }
        }

        $this->info(sprintf('状态tab查询范围%s %d 条', $dryRun ? '将更新' : '已更新', $updated));
    }

    private function patchEmptySubModuleIds(bool $dryRun): void
    {
        $modules = LowCodePersonalizeModule::query()
            ->where('module_type', 'crowd_patients')
            ->get(['id', 'title', 'disease_code', 'scene_code', 'org_code', 'sub_module_ids']);

        $updated = 0;
        $idsByContext = [];
        foreach ($modules as $module) {
            if ($module->resolveManageProjectIds() !== []) {
                continue;
            }

            $diseaseCode = trim((string) $module->disease_code);
            $sceneCode = trim((string) $module->scene_code);
            $orgCode = trim((string) $module->org_code);
            $contextKey = $diseaseCode . "\0" . $sceneCode . "\0" . $orgCode;

            if (!array_key_exists($contextKey, $idsByContext)) {
                try {
                    $idsByContext[$contextKey] = $this->enabledProjectIds($diseaseCode, $sceneCode, $orgCode);
                } catch (Throwable $e) {
                    $idsByContext[$contextKey] = null;
                    $this->warn(sprintf(
                        '菜单 #%s 获取管理方案失败 disease=%s scene=%s org=%s : %s',
                        $module->id,
                        $diseaseCode,
                        $sceneCode,
                        $orgCode,
                        $e->getMessage()
                    ));
                    continue;
                }
            }

            $ids = $idsByContext[$contextKey];
            if (null === $ids) {
                continue;
            }
            if ($ids === []) {
                $this->line(sprintf('菜单 #%s 没有可用管理方案，跳过', $module->id));
                continue;
            }

            $updated++;
            $this->line(sprintf('菜单 #%s %s 将填充 %d 个管理方案', $module->id, $module->title, count($ids)));
            if (!$dryRun) {
                $module->sub_module_ids = $ids;
                $module->save();
            }
        }

        $this->info(sprintf('空管理方案菜单%s %d 条', $dryRun ? '将填充' : '已填充', $updated));
    }

    /**
     * @return array<int, string>
     */
    private function enabledProjectIds(string $diseaseCode, string $sceneCode, string $orgCode): array
    {
        $connection = 'bmp_cheetah_medical_platform';
        if (!Config::has('database.connections.' . $connection)) {
            Config::set(
                'database.connections.' . $connection,
                config('low-code.bmo-baseline.database.default')
            );
        }

        return DB::connection($connection)
            ->table('com_schedule_project')
            ->selectRaw('cast(`id` as char) as id')
            ->where('status', 1)
            ->where('disease_code', $diseaseCode)
            ->where('scene_code', $sceneCode)
            ->where('is_deleted', 0)
            ->when('' !== $orgCode, fn ($query) => $query->where('org_code', $orgCode))
            ->whereNull('pre_id')
            ->pluck('id')
            ->map(fn ($id) => trim((string) $id))
            ->filter(fn (string $id) => '' !== $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function queryScopeMapping(): array
    {
        $mapping = [];
        foreach ($this->loadTemplateLists() as $item) {
            $name = trim((string) ($item['admin_name'] ?? ''));
            $source = trim((string) ($item['query_scope'] ?? ''));
            if ('' !== $name && '' !== $source) {
                $mapping[$name] = $source;
            }
        }

        return $mapping;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadTemplateLists(): array
    {
        $templatePath = (string) config('low-code.init.default_template', 'template.json');
        if ('' === pathinfo($templatePath, PATHINFO_EXTENSION)) {
            $templatePath .= '.json';
        }

        $templateFile = '';
        foreach (['storage_path', 'app_path', 'base_path'] as $resolver) {
            $candidate = $resolver($templatePath);
            if (is_file($candidate)) {
                $templateFile = $candidate;
                break;
            }
        }
        if ('' === $templateFile) {
            $this->warn('未找到初始化模板，状态tab按内置名称回填: ' . $templatePath);

            return [];
        }

        $decoded = json_decode((string) file_get_contents($templateFile), true);
        if (!is_array($decoded)) {
            $this->warn('初始化模板格式错误，状态tab按内置名称回填');

            return [];
        }

        $lists = [];
        foreach ($decoded as $sceneTemplates) {
            if (!is_array($sceneTemplates)) {
                continue;
            }
            foreach ($sceneTemplates as $sceneTemplate) {
                foreach ((array) ($sceneTemplate['list'] ?? []) as $item) {
                    if (is_array($item)) {
                        $lists[] = $item;
                    }
                }
            }
        }

        $this->line('读取模板: ' . $templateFile);

        return $lists;
    }
}
