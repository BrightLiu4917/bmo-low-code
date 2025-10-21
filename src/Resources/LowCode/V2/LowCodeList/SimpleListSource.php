<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Resources\LowCode\V2\LowCodeList;

use App\Models\LowCode\LowCodeList;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LowCodeList
 */
final class SimpleListSource extends JsonResource
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
                'admin_name',
                'code',
                'parent_code',
                'crowd_type_code',
                'route_group',
            ]),

            // TODO: 兼容原逻辑，待完善
            'crowd_type_code' => $this->code,
            'crowd_type_name' => $this->admin_name,
            'crowd_type_color' => null,
            'crowd_type_weight' => 0,
        ];
    }
}
