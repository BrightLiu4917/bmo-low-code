<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Services;

use Illuminate\Support\Collection;
use BrightLiu\LowCode\Traits\Context\WithAuthContext;

/**
 * 服务人群工具模块 处理相关
 */
final class CrowdKitService extends LowCodeBaseService
{
    use WithAuthContext;

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
     * 格式化列分组
     */
    public function formatColumnGroup(array $data): Collection
    {
        return collect($data)
            ->map(
                fn ($item) => [
                    'id' => $item['group_id'],
                    'name' => $item['group_name'],
                    'columns' => $this->formatColumns($item['org_col_groups']),
                ]
            )
            ->filter(fn ($item) => !empty($item['columns']))
            ->values();
    }

    /**
     * 格式化列集合
     */
    public function formatColumns(array|Collection $columnGroup): Collection
    {
        $priorityColumns = ['rsdnt_nm', 'id_crd_no', 'gdr_cd', 'slf_tel_no', 'bth_dt', 'curr_addr'];

        $fixedColumns = ['rsdnt_nm', 'id_crd_no'];

        $hiddenColumns = ['empi', 'is_deleted', 'gmt_created', 'gmt_modified'];

        return collect($columnGroup)
            ->map(
                fn ($sitem) => [
                    'id' => $sitem['col_id'],
                    'name' => $sitem['col_title'],
                    'type' => $sitem['data_type'],
                    'column' => str_replace('.', '_', $sitem['col_nm']),
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
    public function resolveGroupName(int $groupId, mixed $default = null,int $selectType = 0): mixed
    {
        if (empty($groupId)) {
            return $default;
        }

        $groupNameMapping = self::$groupNameMapping ??= rescue(
            fn () => array_column(
                array_filter(
                    BmpCheetahMedicalCrowdkitApiService::instance()->getCrowds(selectType: $selectType),
                    fn ($item) => empty($item['select_type']) || $item['select_type'] != 9
                ),
                'group_name',
                'id'
            ),
            []
        );

        return $groupNameMapping[$groupId] ?? $default;
    }
}
