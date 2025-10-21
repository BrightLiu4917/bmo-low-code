<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Resources\DatabaseSource;

use Illuminate\Http\Request;
use App\Models\DatabaseSource;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DatabaseSource
 */
final class ShowResource extends JsonResource
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
                'disease_code',
                'code',
                'name',
                'host',
                'database',
                'table',
                'port',
                'options',
                'source_type',
                'created_at',
                'updated_at',
            ]),
            'creator_name'            => $this->creator_name ?? '',
            'updater_name'            => $this->updater_name ?? '',
            'disease_name'            => $this->disease_name ?? '',
            'source_type_definition'  => $this->source_type_definition ?? ''
        ];
    }
}
