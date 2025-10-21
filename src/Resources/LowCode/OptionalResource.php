<?php

declare(strict_types=1);
namespace BrightLiu\LowCode\Resources\LowCode;

use Illuminate\Http\Resources\Json\JsonResource;

final class OptionalResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'label' => $this['group_name'] ?? '',
            'value' => $this['user_group_id'] ?? $this['id'] ?? '',
        ];
    }
}
