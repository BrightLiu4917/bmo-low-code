<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Resources\LowCode\LowCodeCrowdLayer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ListResource extends JsonResource
{
    /**
     * @param Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id ?? 0,
            'title' => $this->title ?? '',
            'module_id' => $this->module_id ?? '',
            'crowd_id' => $this->crowd_id ?? '',
            'created_at' => $this->created_at ?? null,
            'preset_filters' => $this->preset_filters ?? [],
        ];
    }
}
