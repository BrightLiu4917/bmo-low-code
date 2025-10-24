<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLowCodeTemplateHasPartsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('low_code_template_has_parts')) {
            Schema::create('low_code_template_has_parts', function (Blueprint $table) {
                // 主键
                $table->bigIncrements('id')->comment('主键');

                // 关系字段
                $table->string('part_code', 64)->default('')->comment('零件唯一编码');
                $table->string('template_code', 64)->default('')->comment('模板唯一编码');

                // 状态和权重字段
                $table->unsignedTinyInteger('locked')->default(0)->comment('是否锁定字段：1锁定,0未锁定');
                $table->unsignedInteger('weight')->default(0)->comment('权重(降序)');

                // 时间戳字段
                $table->dateTime('created_at')->nullable()->comment('创建时间');
                $table->dateTime('updated_at')->nullable()->comment('更新时间');
                $table->dateTime('deleted_at')->nullable()->comment('删除时间');

                // 索引
                $table->index('part_code', 'idx_part_code');
                $table->index('template_code', 'idx_template_code');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('low_code_template_has_parts')) {
            Schema::dropIfExists('low_code_template_has_parts');
        }
    }
}