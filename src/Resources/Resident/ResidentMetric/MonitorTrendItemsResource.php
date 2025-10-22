<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Resources\Resident\ResidentMetric;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class MonitorTrendItemsResource extends JsonResource
{
    /**
     * @param Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'datetime' => $this->fill_date ?? '',
            'date' => Carbon::make($this->fill_date ?? '')->format('Y-m-d'),
            'value' => $this->col_value ?? '',
        ];
    }
}
