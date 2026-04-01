<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Resources\Resident\ResidentManagement;

use Illuminate\Http\Resources\Json\JsonResource;

final class PreResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'checked' => $this['checked'],
            'basic_info' => [],
        ];
    }
}
