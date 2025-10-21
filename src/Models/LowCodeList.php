<?php

namespace App\Models\LowCode;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use BrightLiu\LowCode\Models\Traits\Cacheable\NewEloquentBuilder;
use BrightLiu\LowCode\Enums\Model\LowCode\LowCodeList\ListTypeEnum;
use BrightLiu\LowCode\Models\Traits\OrgDiseaseRelation;
use BrightLiu\LowCode\Models\Traits\OrgRelation;
use BrightLiu\LowCode\Models\Traits\Cacheable\CacheableModel;
use BrightLiu\LowCode\Models\Traits\UniqueCodeRelation;
use BrightLiu\LowCode\Models\Traits\AdministratorRelation;


/**
 * @Class
 * @Description:
 * @created    : 2025-10-01 11:51:47
 * @modifier   : 2025-10-01 11:51:47
 */
class LowCodeList extends LowCodeBaseModel
{
    use
//        NewEloquentBuilder,
        SoftDeletes,
        UniqueCodeRelation,
        OrgDiseaseRelation,
        OrgRelation,
//        AdministratorRelation,
        CacheableModel;

    protected $casts = [
        'disease_code' => 'string', // varchar(32) NOT NULL DEFAULT ''
        'code' => 'string', // varchar(32) NOT NULL DEFAULT ''
        'parent_code' => 'string', // varchar(32) NOT NULL DEFAULT ''
        'org_code' => 'string', // varchar(64) NOT NULL DEFAULT ''

        'list_type' => 'integer', // Assuming it's an integer type, adjust if necessary

        'admin_name' => 'string', // Assuming it's a string type, adjust if necessary
        'family_doctor_name' => 'string', // Assuming it's a string type, adjust if necessary
        'mobile_doctor_name' => 'string', // Assuming it's a string type, adjust if necessary

        'admin_weight' => 'integer', // Assuming it's an integer type, adjust if necessary
        'family_doctor_weight' => 'integer', // Assuming it's an integer type, adjust if necessary
        'mobile_doctor_weight' => 'integer', // Assuming it's an integer type, adjust if necessary

        'crowd_type_code' => 'string', // varchar(32) NOT NULL DEFAULT ''
        'template_code_filter' => 'string', // Assuming it's a string type, adjust if necessary
        'template_code_column' => 'string', // Assuming it's a string type, adjust if necessary
        'template_code_field' => 'string', // Assuming it's a string type, adjust if necessary
        'template_code_button' => 'string', // Assuming it's a string type, adjust if necessary
        'template_code_top_button' => 'string', // Assuming it's a string type, adjust if necessary

        'route_group' => 'json', // JSON DEFAULT NULL

        'append_field_json' => 'json', // JSON DEFAULT NULL
        'append_column_json' => 'json', // JSON DEFAULT NULL
        'append_filter_json' => 'json', // JSON DEFAULT NULL
        'append_button_json' => 'json', // JSON DEFAULT NULL
        'append_top_button_json' => 'json', // JSON DEFAULT NULL

        'remove_field_json' => 'json', // JSON DEFAULT NULL
        'remove_filter_json' => 'json', // JSON DEFAULT NULL
        'remove_column_json' => 'json', // JSON DEFAULT NULL
        'remove_button_json' => 'json', // JSON DEFAULT NULL
        'remove_top_button_json' => 'json', // JSON DEFAULT NULL
        'default_order_by_json' => 'json', // Assuming it's a JSON field that should be cast to array
        'preset_condition_json' => 'json' // JSON DEFAULT NULL
    ];

    /**
     * @var string[]
     */
    protected $fillable
        = ["id", "disease_code", "code", "parent_code", "org_code",
           "admin_name", "family_doctor_name", "mobile_doctor_name",
           "admin_weight", "family_doctor_weight", "mobile_doctor_weight",
           "crowd_type_code", "template_code_filter", "template_code_column",
           "template_code_field", "template_code_button",
           "template_code_top_button", "route_group", "append_field_json",
           "append_column_json", "append_filter_json", "append_button_json",
           "append_top_button_json", "remove_field_json", "remove_filter_json",
           "remove_column_json", "remove_button_json", "remove_top_button_json",'default_order_by_json',
           "creator_id", "updater_id", "created_at", "updated_at",
           "deleted_at",'list_type','preset_condition_json'
        ];


    public function parent(): BelongsTo
    {
        return $this->belongsTo(LowCodeList::class, 'parent_code', 'code');
    }

    public function crowdType(): BelongsTo
    {
        return $this->belongsTo(CrowdType::class, 'crowd_type_code', 'code');
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(
            LowCodeTemplate::class, 'template_code_column', 'code'
        );
    }

    public function filter(): BelongsTo
    {
        return $this->belongsTo(
            LowCodeTemplate::class, 'template_code_filter', 'code'
        );
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(
            LowCodeTemplate::class, 'template_code_field', 'code'
        );

    }

    public function button(): BelongsTo
    {
        return $this->belongsTo(
            LowCodeTemplate::class, 'template_code_button', 'code'
        );
    }


    public function topButton(): BelongsTo
    {
        return $this->belongsTo(
            LowCodeTemplate::class, 'template_code_top_button', 'code'
        );
    }

    /**
     * 列表类型
     * @return Attribute
     */
    public function listTypeDefinition(): Attribute
    {
        return ListTypeEnum::makeAttribute($this);
    }

}
