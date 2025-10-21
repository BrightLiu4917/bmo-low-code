<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Resources\Disease;

use Illuminate\Http\Request;
use App\Models\LowCodeDisease;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LowCodeDisease
 */
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
            ...$this->only([
                'id',
                'name',
                'code',
                'extraction_pattern',
                'weight',
                'creator_id',
                'updater_id',
                'created_at',
                'updated_at',
            ]),
            'creator_name'            => $this->creator_name ?? '',
            'updater_name'            => $this->updater_name ?? '',
        ];
    }
}
