<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdminPermissionTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('data_permissions')) {
            Schema::create('data_permissions', function (Blueprint $table) {
                // 主键
                $table->bigIncrements('id')->comment('主键');

                // 基础字段
                $table->string('title', 32)->default('')->comment('标题');
                $table->string('code', 32)->unique()->default('')->comment('唯一编码');
                $table->string('symbol', 32)->default('')->comment('操作符号');
                $table->string('permission_key', 32)->default('')->comment('where 字段');
                // 时间戳字段
                $table->dateTime('created_at')->nullable()->comment('创建时间');
                $table->dateTime('updated_at')->nullable()->comment('更新时间');
                $table->dateTime('deleted_at')->nullable()->comment('删除时间');

                // 索引 - 使用 Laravel 的标准方法
                $table->index(['code'], 'idx_code');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('data_permissions')) {
            Schema::dropIfExists('data_permissions');
        }
    }
}