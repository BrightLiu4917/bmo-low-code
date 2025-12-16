<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Resources\Resident\ResidentArchive;

use BrightLiu\LowCode\Support\Attribute\Conversion;
use BrightLiu\LowCode\Support\Attribute\Converters\Age;
use BrightLiu\LowCode\Support\Attribute\Converters\BthDt;
use BrightLiu\LowCode\Support\Attribute\Converters\GdrCd;
use BrightLiu\LowCode\Support\Attribute\Converters\HeightArrHeight;
use BrightLiu\LowCode\Support\Attribute\Converters\IdCrdNo;
use BrightLiu\LowCode\Support\Attribute\Converters\NtnCd;
use BrightLiu\LowCode\Support\Attribute\Converters\SlfTelNo;
use BrightLiu\LowCode\Support\Attribute\Converters\WeightArrWeight;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

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

        // 按 白名单/黑名单 过滤字段
        if (is_array($fillable = $this->fillable())) {
            $columns = $columns->filter(fn ($item) => in_array($item['column'] ?? '', $fillable, true))->values();
        } elseif (is_array($guarded = $this->guarded())) {
            $columns = $columns->filter(fn ($item) => !in_array($item['column'] ?? '', $guarded, true))->values();
        }

        if ($columns->isEmpty()) {
            return new MissingValue();
        }

        $conversion = $this->fetchConversion();

        $readonly = $this->readonly();

        return [
            'id' => $this['id'] ?? '',
            'name' => $this['name'] ?? '',
            'columns' => $columns->map(function ($column) use ($conversion, $attributes, $readonly) {
                $convertData = $conversion->fetchOnce((string) ($column['column'] ?? ''), $attributes);

                $value = $convertData->getValue($column['value'] ?? null);

                // 判定是否为只读(优先级：转换器指定 > 资源指定)
                if (is_null($isReadonly = $convertData->getReadonly(null))) {
                    $isReadonly = is_array($readonly) && in_array($column['column'] ?? '', $readonly, true);
                }

                return array_merge($column, [
                    'value' => $value,
                    'value.variant' => $convertData->getVariant($value),
                    'unit' => $convertData->getUnit(''),
                    'readonly' => $isReadonly,
                    'metadata' => $convertData->getMetadata([]),
                ]);
            }),
        ];
    }

    protected function fetchConversion(): Conversion
    {
        return Conversion::make([
            Age::class,
            BthDt::class,
            GdrCd::class,
            IdCrdNo::class,
            SlfTelNo::class,
            NtnCd::class,
            HeightArrHeight::class,
            WeightArrWeight::class,
        ]);
    }

    /**
     * 白名单
     * PS: 优先级高于黑名单
     */
    protected function fillable(): ?array
    {
        return null;
    }

    /**
     * 黑名单
     */
    public function guarded(): ?array
    {
        return null;
    }

    /**
     * 只读
     */
    public function readonly(): ?array
    {
        return null;
    }
}
