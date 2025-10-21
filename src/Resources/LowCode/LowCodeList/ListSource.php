<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Resources\LowCode\LowCodeList;

use Illuminate\Http\Request;
use App\Models\LowCode\LowCodeList;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LowCodeList
 */
final class ListSource extends JsonResource
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
                "admin_name",
                "code",
                "parent_code",
//                "crowd_type_code",
                "route_group",
                "admin_weight",
                "creator_id",
                "updater_id",
                'created_at',
                'updated_at',
            ]),
            'creator_name' => $this->creator_name ?? '',
            'updater_name' => $this->updater_name ?? '',

//            'crowd_type_name' => $this->crowdType->name ?? '',
//            'crowd_type_color' => $this->crowdType->color ?? null,
//            'crowd_type_weight' => $this->crowdType->weight ?? 0,
        ];
    }
}
