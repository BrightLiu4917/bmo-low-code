<?php

namespace App\Models\LowCode;

use BrightLiu\LowCode\Models\Traits\ModelFetch;
use BrightLiu\LowCode\Models\Traits\OrgRelation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use BrightLiu\LowCode\Models\Traits\OrgDiseaseRelation;
use BrightLiu\LowCode\Models\Traits\UniqueCodeRelation;
use BrightLiu\LowCode\Models\Traits\AdministratorRelation;
use BrightLiu\LowCode\Enums\Model\LowCode\LowCodePart\PartTypeEnum;
use BrightLiu\LowCode\Enums\Model\LowCodeTemplate\ContentTypeEnum;

/**
 * @Class
 * @Description:
 * @created    : 2025-10-02 16:21:32
 * @modifier   : 2025-10-02 16:21:32
 */
class LowCodePart extends LowCodeBaseModel
{
    /**
     * @var string
     */
    protected  $primaryKey = 'code';

    /**
     * @var bool
     */
    public $incrementing = false;


    use
        ModelFetch,
        SoftDeletes,
        OrgRelation,

//        AdministratorRelation
         UniqueCodeRelation;
    protected $fillable = [
        "id",//bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
        "name",//varchar(64) NOT NULL DEFAULT '' COMMENT '名字',
        "code",//varchar(64) NOT NULL DEFAULT '' COMMENT '唯一编码',
        "org_code",//varchar(64) NOT NULL DEFAULT '' COMMENT '机构编码',
        "part_type",//int(10) unsigned NOT NULL DEFAULT '0' COMMENT '零件类型 1系统内置,2客户自定义',
        "description",//varchar(200) NOT NULL DEFAULT '' COMMENT '描述',
        "content_type",//tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '类型:1列头展示，2筛选项，3操作栏按钮，4顶部按钮，5查询字段集合',
        "content",//json DEFAULT NULL COMMENT '组件内容',
        "weight",//int(10) unsigned NOT NULL DEFAULT '0' COMMENT '权重(降序)',
        "creator_id",//bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '创建人ID',
        "updater_id",//bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '更新人ID',
        "created_at",//datetime DEFAULT NULL COMMENT '创建时间',
        "updated_at",//datetime DEFAULT NULL COMMENT '更新时间',
        "deleted_at",//datetime DEFAULT NULL COMMENT '删除时间',
    ];



    /**
     * @var string[]
     */
    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'code' => 'string',
        'org_code' => 'string',
        'part_type' => 'integer',
        'description' => 'string',
        'content_type' => 'integer',
        'content' => 'array',
        'weight' => 'integer',
        'creator_id' => 'integer',
        'updater_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * @return Attribute
     */
    public function partTypeDefinition(): Attribute
    {
        return PartTypeEnum::makeAttribute($this);
    }

    /**
     * @return Attribute
     */
    public function contentTypeDefinition(): Attribute
    {
        return ContentTypeEnum::makeAttribute($this);
    }
}
