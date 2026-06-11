<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Resources\Resident\ResidentArchive;

use BrightLiu\LowCode\Services\PatientColumnContext;
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
                $key = $column['column'] ?? '';

                $metadata = PatientColumnContext::getMeta($key);

                return array_merge($column, [
                    'name' => !empty($metadata['field_short_name']) ? $metadata['field_short_name'] : $column['name'],
                    'value' => '',
                    'value.variant' => '',
                    'unit' => $metadata['unit'] ?? '',
                    'readonly' => isset($column['_is_editable']) ? ($column['_is_editable'] ?? 0) != 1 : false,
                    'metadata' => $metadata,
                    'enum' => PatientColumnContext::getEnumMappingValue($key),
                    'required' => isset($metadata['is_required']) ? $metadata['is_required'] : $column['required']
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
