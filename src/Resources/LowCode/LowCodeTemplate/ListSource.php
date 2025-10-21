<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Resources\LowCode\LowCodeTemplate;

use Illuminate\Http\Request;
use App\Models\LowCode\LowCodeTemplate;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LowCodeTemplate
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
        ];
    }
}
