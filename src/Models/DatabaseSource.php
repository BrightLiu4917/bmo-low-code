<?php

declare(strict_types = 1);

namespace BrightLiu\LowCode\Models;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\SoftDeletes;
use BrightLiu\LowCode\Models\Traits\ModelFetch;
use BrightLiu\LowCode\Models\Traits\DiseaseRelation;
use BrightLiu\LowCode\Models\Traits\Cacheable\CacheableModel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use BrightLiu\LowCode\Models\Traits\UniqueCodeRelation;
use BrightLiu\LowCode\Enums\Model\DatabaseSource\SourceTypeEnum;
use BrightLiu\LowCode\Models\Traits\Cacheable\NewEloquentBuilder;
/**
 * 数据库源
 *
 */
final class DatabaseSource extends LowCodeBaseModel
{
    use
//        NewEloquentBuilder,
        SoftDeletes,
        CacheableModel,
        DiseaseRelation,
        UniqueCodeRelation,
        ModelFetch;

    /**
     * @var array|string[]
     */
    protected  $fillable =
        [
            "id",//bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
            "disease_code",//varchar(64) NOT NULL DEFAULT '' COMMENT '病种编码',
            "code",//varchar(64) NOT NULL DEFAULT '' COMMENT '编码',
            "name",//varchar(64) NOT NULL DEFAULT '' COMMENT '名字',
            "host",//varchar(64) NOT NULL DEFAULT '' COMMENT '主机地址',
            "database",//varchar(64) NOT NULL DEFAULT '' COMMENT '数据库',
            "table",//varchar(64) NOT NULL DEFAULT '' COMMENT '表',
            "port",//varchar(64) NOT NULL DEFAULT '' COMMENT '端口',
            "username",//varchar(255) NOT NULL DEFAULT '' COMMENT '账号',
            "password",//varchar(255) NOT NULL DEFAULT '' COMMENT '密码',
            "options",//json DEFAULT NULL COMMENT '扩展项',
            "source_type",
            //tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '数据源类型:1数据,2业务',
            "creator_id",
            //bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '创建人ID',
            "updater_id",
            //bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '更新人ID',
            "created_at",//datetime DEFAULT NULL COMMENT '创建时间',
            "updated_at",//datetime DEFAULT NULL COMMENT '更新时间',
            "deleted_at",//datetime DEFAULT NULL COMMENT '删除时间',
        ];

    /**
     * @var array|string[]
     */
    protected  $casts
        = [
            'id'           => 'integer',
            'disease_code' => 'string',
            'code'         => 'string',
            'name'         => 'string',
            'host'         => 'string',
            'database'     => 'string',
            'table'        => 'string',
            'port'         => 'string',
            'username'     => 'string',
            'password'     => 'string',
            'options'      => 'array',
            'source_type'  => 'integer',
            'creator_id'   => 'integer',
            'updater_id'   => 'integer',
            'created_at'   => 'datetime',
            'updated_at'   => 'datetime',
            'deleted_at'   => 'datetime',
        ];

    /**
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function sourceTypeDefinition(): Attribute
    {
        return SourceTypeEnum::makeAttribute($this);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function username(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Crypt::decryptString($value), set: fn ($value,
        ) => Crypt::encryptString($value),
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function password(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Crypt::decryptString($value), set: fn ($value,
        ) => Crypt::encryptString($value),
        );
    }
}
