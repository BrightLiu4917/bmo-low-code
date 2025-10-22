<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDatabaseSourcesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('database_sources')) {
            Schema::create('database_sources', function (Blueprint $table) {
                // 主键和基础字段
                $table->bigIncrements('id')->comment('主键');
                $table->string('disease_code', 32)->default('')->comment('病种编码');
                $table->string('code', 32)->default('')->unique()->comment('编码');
                $table->string('name', 64)->default('')->comment('名字');

                // 数据库连接信息
                $table->string('host', 64)->default('')->comment('主机地址');
                $table->string('database', 64)->default('')->comment('数据库');
                $table->string('table', 64)->default('')->comment('表');
                $table->string('port', 64)->default('')->comment('端口');
                $table->string('username', 255)->default('')->comment('账号');
                $table->string('password', 255)->default('')->comment('密码');

                // 其他字段
                $table->json('options')->nullable()->comment('扩展项');
                $table->unsignedTinyInteger('source_type')->default(0)->comment('数据源类型:1数据,2业务');

                // 操作人字段
                $table->unsignedBigInteger('creator_id')->default(0)->comment('创建人ID');
                $table->unsignedBigInteger('updater_id')->default(0)->comment('更新人ID');

                // 时间戳字段
                $table->dateTime('created_at')->nullable()->comment('创建时间');
                $table->dateTime('updated_at')->nullable()->comment('更新时间');
                $table->dateTime('deleted_at')->nullable()->comment('删除时间');

                // 索引
                $table->index('creator_id', 'idx_creator_id');
                $table->index('updater_id', 'idx_updater_id');
                $table->index('disease_code', 'idx_disease_code');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('database_sources')) {
            Schema::dropIfExists('database_sources');
        }
    }
}