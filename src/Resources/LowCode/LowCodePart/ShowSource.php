<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Resources\LowCode\LowCodePart;

use Illuminate\Http\Request;
use App\Models\LowCode\LowCodePart;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LowCodePart
 */
final class ShowSource extends JsonResource
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
                'part_type',
                'content_type',
                'description',
                'creator_id',
                'updater_id',
                'created_at',
                'content',
                'updated_at',
            ]),
            'creator_name' => $this->creator_name ?? '',
            'updater_name' => $this->updater_name ?? '',
//            'content_type_definition' => $this->content_type_definition ?? '',
            'part_type_definition' => $this->part_type_definition ?? '',
        ];
    }
}
