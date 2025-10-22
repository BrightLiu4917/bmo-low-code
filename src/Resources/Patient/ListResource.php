<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Resources\patient;

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
        return  config('low-code.patient.list-resource');
    }
}
