<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Resources\Resident\ResidentArchive;

use BrightLiu\LowCode\Support\Attribute\Conversion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InfoResource extends JsonResource
{
    /**
     * @param Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        $columns = collect($this['columns'] ?? []);

        $attributes = $columns->mapWithKeys(fn ($item) => [$item['column'] => $item['value']])->toArray();

        $conversion = $this->fetchConversion();

        return [
            'id' => $this['id'] ?? '',
            'name' => $this['name'] ?? '',
            'columns' => $columns->map(function ($column) use ($conversion, $attributes) {
                $convertData = $conversion->fetchOnce((string) ($column['column'] ?? ''), $attributes);

                return array_merge($column, [
                    'value' => $convertData->getValue($column['value'] ?? null),
                    'value.variant' => $convertData->getVariant(''),
                    'unit' => $convertData->getUnit(''),
                    'readonly' => $convertData->getReadonly(false),
                    'metadata' => $convertData->getMetadata([]),
                ]);
            }),
        ];
    }

    protected function fetchConversion(): Conversion
    {
        return Conversion::make();
    }
}
