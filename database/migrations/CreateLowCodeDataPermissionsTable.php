<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDataPermissionsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('low_code_data_permissions')) {
            Schema::create('low_code_data_permissions', function (Blueprint $table) {
                // 主键
                $table->bigIncrements('id')->comment('主键');

                // 权限相关字段
                $table->string('disease_code', 32)->default('')->comment('病种编码');
                $table->string('permission_key', 32)->default('')->comment('权限字段（纳管机构编码）');
                $table->string('code', 32)->unique()->default('')->comment('编码');
                $table->string('title', 32)->default('')->comment('标题');
                $table->string('operation_symbol', 32)->default('in')->comment('操作符号（<,<=,=,>=,>,in,not in）');

                // 时间戳字段
                $table->dateTime('created_at')->nullable()->comment('创建时间');
                $table->dateTime('updated_at')->nullable()->comment('更新时间');
                $table->dateTime('deleted_at')->nullable()->comment('删除时间');

                // 索引
                $table->primary('id');
                $table->unique(['disease_code', 'code', 'permission_key'], 'uk_permission');
                $table->index('disease_code', 'idx_disease_code');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('low_code_data_permissions')) {
            Schema::dropIfExists('low_code_data_permissions');
        }
    }
}