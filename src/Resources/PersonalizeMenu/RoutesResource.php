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
        return $this->metadata;
    }
}
