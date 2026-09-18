<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Resources\PersonalizeMenu;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\PersonalizeModule
 */
final class RoutesResource extends JsonResource
{
    /**
     * @param Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        $metadata = is_array($this->metadata ?? null) ? $this->metadata : [];

        $metadata['id'] = $this->id;
        $metadata['meta']['personalize_module_id'] = $this->id;
        $metadata['meta']['query'] = [
            'group_id' => $this->module_id ?? 0,
            'group_ids' => $this->resource->resolveCrowdIds(),
        ];

        return $metadata;
    }
}
