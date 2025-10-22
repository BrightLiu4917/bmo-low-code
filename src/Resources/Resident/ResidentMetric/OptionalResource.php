<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Resources\Resident\ResidentMetric;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OptionalResource extends JsonResource
{
    /**
     * @param Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'title' => $this['field_name'] ?? '',
            'indicator_id' => $this['field'] ?? '',
        ];
    }
}
