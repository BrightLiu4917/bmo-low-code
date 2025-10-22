<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Resources\Resident\ResidentMetric;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \BrightLiu\LowCode\Models\Resident\ResidentMonitorMetric
 */
class MonitorListResource extends JsonResource
{
    /**
     * @param Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'title' => $this->metric_title,
            'metric_id' => $this->metric_id,
            // TODO: 指标OPTIONS待实现
            'options' => [],
        ];
    }
}
