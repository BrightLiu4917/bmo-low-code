<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Resources\PersonalizeMenu;

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
            'module_type' => $this->module_type ?? '',
            'metadata' => $this->metadata ??' ',
            'created_at' => $this->created_at ?? null,
        ];
    }
}
