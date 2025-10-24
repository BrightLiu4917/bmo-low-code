<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePersonalizeModulesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('personalize_modules')) {
            Schema::create('personalize_modules', function (Blueprint $table) {
                // 主键
                $table->bigIncrements('id')->comment('主键');

                // 模块相关字段
                $table->string('disease_code', 32)->default('')->comment('病种编码');
                $table->string('title', 32)->default('')->comment('标题');
                $table->string('module_id', 32)->default('')->comment('模块ID');
                $table->string('module_type', 32)->default('')->comment('模块类型');

                // JSON 字段
                $table->json('metadata')->nullable()->comment('元数据');

                // 权重和时间戳
                $table->unsignedInteger('weight')->default(0)->comment('权重(降序)');
                $table->dateTime('created_at')->nullable()->comment('创建时间');
                $table->index('module_id', 'idx_module_id');
                $table->index('disease_code', 'idx_disease_code_org_code');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('personalize_modules')) {
            Schema::dropIfExists('personalize_modules');
        }
    }
}