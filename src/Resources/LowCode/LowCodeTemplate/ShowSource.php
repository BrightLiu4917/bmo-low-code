<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Resources\LowCode\LowCodeTemplate;

use Illuminate\Http\Request;
use App\Models\LowCode\LowCodeTemplate;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LowCodeTemplate
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
                "disease_code",
                "code",
                "org_code",
                "template_type",
                "content_type",
                "description",
                "weight",
                'creator_id',
                'updater_id',
                'created_at',
                'updated_at',
            ]),
            'creator_name' => $this->creator_name ?? '',
            'updater_name' => $this->updater_name ?? '',
//            'content_type_definition' => $this->content_type_definition ?? '',
            'template_type_definition' => $this->template_type_definition ?? '',
            'bind_part_list' => $this->bindPartList->map(function ($part) {
                return [
                    'id'=> $part->id ?? 0,
                    'code' => $part->code ?? '',
                    'name' => $part->name ?? '',
                    'content_type'=> $part->content_type ?? 0,
                    'template_type'=> $part->template_type ?? 0,
                    'description' => $part->description ?? '',
                    'weight' => $part->pivot->weight ?? 0,
                    'content' => $part->content ?? null,
                    'locked' => $part->pivot->locked ?? 0,
                ];
            }),
        ];
    }
}
