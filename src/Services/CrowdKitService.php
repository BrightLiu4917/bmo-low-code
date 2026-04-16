<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services;

use BrightLiu\LowCode\Enums\Foundation\Logger;
use BrightLiu\LowCode\Support\CrowdConnection;
use BrightLiu\LowCode\Traits\Context\WithContext;
use Gupo\BetterLaravel\Traits\WithRescue;
use Illuminate\Support\Collection;

/**
 * 服务人群工具模块 处理相关
 */
final class CrowdKitService extends LowCodeBaseService
{
    use WithContext, WithRescue;

    /**
     * 人群分类缓存
     */
    protected static ?array $groupNameMapping = null;

    /**
     * 获取人群可选列
     */
    public function getOptionalColumns(): Collection
    {
        $columnGroup = BmpCheetahMedicalCrowdkitApiService::instance()->getPatientCrowdColGroup();

        return $this->formatColumnGroup($columnGroup);
    }

    /**
     * 获取可编辑的人群可选列
     */
    public function getEditableOptionalColumns(): Collection
    {
        $columnGroup = collect(BmpCheetahMedicalCrowdkitApiService::instance()->getPatientCrowdColGroup())
            ->map(fn (array $group) => [
                ...$group,
                'org_col_groups' => array_values(array_filter(
                    $group['org_col_groups'] ?? [],
                    fn (array $column) => 1 === (int) ($column['is_editable'] ?? 0)
                )),
            ])
            ->all();

        return $this->formatColumnGroup($columnGroup);
    }

    /**
     * 格式化列分组
     */
    public function formatColumnGroup(array $data): Collection
    {
        $priorityColumns = ['rsdnt_nm', 'slf_tel_no', 'id_crd_no', 'gdr_cd',  'bth_dt', 'curr_addr'];

        return collect($data)
            ->map(
                fn ($item) => [
                    'id' => $item['group_id'],
                    'name' => $item['group_name'],
                    'columns' => $this->formatColumns($item['org_col_groups'], $priorityColumns),
                ]
            )
            ->filter(fn ($item) => !empty($item['columns']))

            // 根据列的优先配置，对列所在的分组进行排序
            ->sortBy(
                fn ($item) => collect($item['columns'])
                    ->map(function ($column) use ($priorityColumns) {
                        $index = array_search($column['column'], $priorityColumns, true);

                        return false !== $index ? $index : PHP_INT_MAX;
                    })
                    ->min()
            )
            ->values();
    }

    /**
     * 格式化列集合
     */
    public function formatColumns(array|Collection $columnGroup, array $priorityColumns = []): Collection
    {
        $requiredColumns = ['rsdnt_nm', 'slf_tel_no', 'id_crd_no', 'gdr_cd',  'bth_dt', 'curr_addr'];

        $fixedColumns = ['rsdnt_nm', 'id_crd_no'];

        $hiddenColumns = ['empi', 'is_deleted', 'gmt_created', 'gmt_modified'];

        return collect($columnGroup)
            ->map(
                fn ($sitem) => [
                    'id' => $sitem['col_id'],
                    'name' => $sitem['col_title'],
                    'type' => $sitem['data_type'],
                    'column' => str_replace('.', '_', $sitem['col_nm']),

                    // 默认值为1，兼容老数据
                    '_is_show' => $sitem['is_show'] ?? 1,
                    '_is_editable' => $sitem['is_editable'] ?? 1,
                ]
            )

            // 处理固定列
            ->map(fn ($sitem) => [...$sitem, 'mixed' => in_array($sitem['column'], $fixedColumns) ? 1 : 0])

            // 处理隐藏列
            ->filter(fn ($sitem) => !in_array($sitem['column'], $hiddenColumns))

            // 处理列优先级
            ->sortBy(function ($sitem) use ($priorityColumns) {
                $index = array_search($sitem['column'], $priorityColumns, true);

                return false !== $index ? $index - count($priorityColumns) : $sitem['column'];
            })

            // 处理必填列
            ->map(fn ($sitem) => [...$sitem, 'required' => in_array($sitem['column'], $requiredColumns) ? 1 : 0])

            ->values();
    }

    /**
     * 合并列信息
     */
    public function combineColumnGroup(array|Collection $columnGroup, array $attributes): Collection
    {
        return collect($columnGroup)->transform(function (array $item) use ($attributes) {
            $item['columns'] = collect($item['columns'])->map(function ($columnItem) use ($attributes) {
                $columnItem['value'] = $attributes[$columnItem['column']] ?? null;

                // TODO: ....
                $columnItem['unit'] = '';
                $columnItem['explanation'] = '';
                $columnItem['explanation_level'] = '';

                return $columnItem;
            });

            return $item;
        });
    }

    /**
     * 解析人群分类名称
     * PS: 人群分类名称不在feature_use_detail表中，需要做额外的查询
     *
     * @param int $groupId 人群分类ID
     */
    public function resolveGroupName(int $groupId, mixed $default = null, int $selectType = 0): mixed
    {
        if (empty($groupId)) {
            return $default;
        }

        $groupNameMapping = self::$groupNameMapping ??= rescue(
            fn () => array_column(
                array_filter(
                    BmpCheetahMedicalCrowdkitApiService::instance()->getCrowds(selectType: $selectType),
                    fn ($item) => empty($item['select_type']) || 9 != $item['select_type']
                ),
                'group_name',
                'id'
            ),
            []
        );

        return $groupNameMapping[$groupId] ?? $default;
    }

    /**
     * 获取用于查询人群分类的机构code
     * PS: 当开启共享机构获取患者的所需人群分类功能时，除了当前机构外，还会获取共享机构的code进行查询
     */
    public function getQueryCrowdTypeOrgCodes(): array
    {
        $orgCodes = [$this->getAffiliatedOrgCode()];

        // 支持从共享机构获取患者的所需人群分类
        try {
            if (config('low-code.crowd-group-from-share-org-enabled', false)) {
                $shareOrgCodes = BmpCheetahMedicalPlatformApiService::instance()->getShareResourceOrgCodes(
                    $this->getAffiliatedOrgCode()
                );

                $orgCodes = array_values(array_unique(array_filter(array_merge($orgCodes, $shareOrgCodes))));
            }
        } catch (\Throwable $e) {
            Logger::LARAVEL->error('获取共享机构code失败，无法从共享机构获取患者的所需人群分类', [
                'affiliated_org_code' => $this->getAffiliatedOrgCode(),
                'error_message' => $e->getMessage(),
            ]);
        }

        return $orgCodes;
    }

    public function getCrowdTypes(string $empi, bool $excludeBaseline = false): array
    {
        $featureCrowdTable = config('low-code.bmo-baseline.database.crowd-type-table', '');
        $userGroupTable = config('low-code.bmo-baseline.database.crowd-group-table', 'user_group');

        // 按empi连表查询人群分类信息(一个人可能属于多个人群分类)
        return CrowdConnection::connection()
            ->table($featureCrowdTable . ' as t1')
            ->join($userGroupTable . ' as t2', 't2.id', '=', 't1.group_id')
            ->whereIn('t2.org_code', $this->getQueryCrowdTypeOrgCodes())
            ->where('t2.disease_code', $this->getDiseaseCode())
            ->where('t2.scene_code', $this->getSceneCode())
            ->where('t1.empi', $empi)
            ->where('t2.is_deleted', 0)
            ->select(['t1.empi', 't1.group_id', 't2.group_name', 't2.select_type'])
            ->get()
            ->when($excludeBaseline, fn (Collection $collection) => $collection->where('select_type', '<>', 9))
            ->values()
            ->toArray();
    }

    public function batchGetCrowdTypes(array $empis, bool $excludeBaseline = false): array
    {
        if (empty($empis)) {
            return [];
        }

        $featureCrowdTable = config('low-code.bmo-baseline.database.crowd-type-table', '');
        $userGroupTable = config('low-code.bmo-baseline.database.crowd-group-table', 'user_group');

        // 按empi连表查询人群分类信息(一个人可能属于多个人群分类)
        return CrowdConnection::connection()
            ->table($featureCrowdTable . ' as t1')
            ->join($userGroupTable . ' as t2', 't2.id', '=', 't1.group_id')
            ->whereIn('t2.org_code', $this->getQueryCrowdTypeOrgCodes())
            ->where('t2.disease_code', $this->getDiseaseCode())
            ->where('t2.scene_code', $this->getSceneCode())
            ->whereIn('t1.empi', $empis)
            ->where('t2.is_deleted', 0)
            ->select(['t1.empi', 't1.group_id', 't2.group_name', 't2.select_type'])
            ->get()
            ->when($excludeBaseline, fn (Collection $collection) => $collection->where('select_type', '<>', 9))
            ->values()
            ->groupBy('empi')
            ->toArray();
    }
}
