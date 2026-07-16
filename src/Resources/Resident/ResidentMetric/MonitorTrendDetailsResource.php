<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Resources\Resident\ResidentMetric;

use BrightLiu\LowCode\Enums\Model\Resident\DataSourceEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class MonitorTrendDetailsResource extends JsonResource
{
    /**
     * @param Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        $dataSource = (int) ($this['data_source'] ?? 0);

        $withBatch = (bool) $request->input('with_batch', false);

        $withWarning = (bool) $request->input('with_warning', false);

        return [
            'id' => $this['id'] ?? 0,
            'datetime' => $this['fill_date'] ?? '',
            'date' => Carbon::make($this['fill_date'] ?? '')->format('Y-m-d'),
            'value' => $this['col_value'] ?? '',
            'data_source' => $dataSource,
            'data_source_name' => DataSourceEnum::make()->translate($dataSource, ''),
            'warning' => $this->when(
                $withWarning,
                fn () => $this['warning'] ?? null
            ),
            'batch' => $this->when(
                $withBatch,
                fn () => empty($this['batch']) ? null : array_column($this['batch'], null, 'metric_id')
            ),
        ];
    }
}
