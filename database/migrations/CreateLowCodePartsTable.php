<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLowCodePartsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('low_code_parts')) {
            Schema::create('low_code_parts', function (Blueprint $table) {
                // 主键和基础字段
                $table->bigIncrements('id')->comment('主键');
                $table->string('name', 64)->default('')->comment('名字');
                $table->string('code', 64)->default('')->unique()->comment('唯一编码');
                $table->string('org_code', 64)->default('')->comment('机构编码');

                // 类型和描述字段
                $table->unsignedInteger('part_type')->default(0)->comment('零件类型 1系统内置,2客户自定义');
                $table->string('description', 200)->default('')->comment('描述');
                $table->unsignedTinyInteger('content_type')->default(0)->comment('类型:1列头展示，2筛选项，3操作栏按钮，4顶部按钮，5查询字段集合');

                // JSON 字段
                $table->json('content')->nullable()->comment('组件内容');

                // 权重和操作人字段
                $table->unsignedInteger('weight')->default(0)->comment('权重(降序)');
                $table->unsignedBigInteger('creator_id')->default(0)->comment('创建人ID');
                $table->unsignedBigInteger('updater_id')->default(0)->comment('更新人ID');

                // 时间戳字段
                $table->dateTime('created_at')->nullable()->comment('创建时间');
                $table->dateTime('updated_at')->nullable()->comment('更新时间');
                $table->dateTime('deleted_at')->nullable()->comment('删除时间');

                // 索引
                $table->index('content_type', 'idx_content_type');
                $table->index('org_code', 'idx_org_code');
                $table->index('creator_id', 'idx_creator_id');
                $table->index('updater_id', 'idx_updater_id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('low_code_parts')) {
            Schema::dropIfExists('low_code_parts');
        }
    }
}