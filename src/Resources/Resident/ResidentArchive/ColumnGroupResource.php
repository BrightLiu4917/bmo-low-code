<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Resources\Resident\ResidentArchive;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

class ColumnGroupResource extends JsonResource
{
    /**
     * @param Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        $columns = collect($this['columns'] ?? []);

        // 自定义排序
        if (false !== ($sort = $this->sort())) {
            $sort = match (true) {
                is_array($sort) => $sort,
                default => []
            };

            $columns = $columns->sortBy(function ($item) use ($sort) {
                $index = array_search($item['column'] ?? '', $sort, true);

                return false !== $index ? $index : PHP_INT_MAX;
            })->values();
        }

        if ($columns->isEmpty()) {
            return new MissingValue();
        }

        return [
            'id' => $this['id'] ?? '',
            'name' => $this['name'] ?? '',
            'columns' => $columns->map(function ($column) {
                return array_merge($column, [
                    'value' => '',
                    'value.variant' => '',
                    'unit' => '',
                    'readonly' => false,
                    'metadata' => [],
                ]);
            }),
        ];
    }

    /**
     * 排序
     * PS: true-按白名单排序；false-不排序；数组-自定义排序
     */
    public function sort(): bool|array
    {
        return false;
    }
}
