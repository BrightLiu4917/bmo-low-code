<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLowCodeCrowdLayersTable extends Migration
{
    public function up()
    {
        Schema::create('low_code_crowd_layers', function (Blueprint $table) {
            $table->id()->comment('主键');

            // 模块相关字段
            $table->string('disease_code', 32)->default('')->comment('病种编码');
            $table->string('org_code', 64)->default('')->comment('机构编码');
            $table->string('module_id', 64)->default('')->comment('模块ID');
            $table->string('module_type', 32)->default('')->comment('模块类型');
            $table->string('title', 32)->default('')->comment('标题');
            $table->string('crowd_id', 64)->default('')->comment('人群分类ID');
            $table->json('preset_filters')->nullable()->comment('预设条件');

            $table->unsignedInteger('weight')->default(0)->comment('权重(降序)');
            $table->dateTime('created_at')->nullable()->comment('创建时间');

            $table->comment('低代码:人群分层表');
            $table->index(['module_id'], 'idx_module_id');
            $table->index(['crowd_id'], 'idx_crowd_id');
            $table->index(['disease_code', 'org_code'], 'idx_disease_code_org_code');
        });
    }

    public function down()
    {
        Schema::dropIfExists('low_code_crowd_layers');
    }
}