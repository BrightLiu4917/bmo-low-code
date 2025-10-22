<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdminPreferencesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('admin_preferences')) {
            Schema::create('admin_preferences', function (Blueprint $table) {
                // 主键
                $table->bigIncrements('id')->comment('主键');

                // 基础字段
                $table->string('disease_code', 32)->default('')->comment('病种编码');
                $table->string('org_code', 32)->default('')->comment('所属机构编码');
                $table->unsignedBigInteger('admin_id')->default(0)->comment('管理员id');
                $table->string('scene', 32)->default('')->comment('场景');
                $table->string('pkey', 64)->default('')->comment('偏好键');

                // JSON 字段
                $table->json('pvalue')->nullable()->comment('偏好值');

                // 时间戳字段
                $table->dateTime('created_at')->nullable()->comment('创建时间');
                $table->dateTime('updated_at')->nullable()->comment('更新时间');

                // 索引 - 使用 Laravel 的标准方法
                $table->index(['disease_code', 'org_code'], 'idx_disease_code_org_code');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('admin_preferences')) {
            Schema::dropIfExists('admin_preferences');
        }
    }
}