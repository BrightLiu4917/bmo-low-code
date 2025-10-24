<?php


use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLowCodeDiseasesTable extends Migration
{
    public function up()
    {
        // 检查表是否存在，避免重复创建
        if (!Schema::hasTable('low_code_diseases')) {
            Schema::create('low_code_diseases', function(Blueprint $table) {
                $table->bigIncrements('id')->comment('主键');
                $table->string('code', 32)->default('')->unique()
                      ->comment('唯一标识(病种编码)');
                $table->string('name', 32)->default('')->comment('名称');
                $table->unsignedInteger('weight')->default(0)
                      ->comment('权重(排序:倒序)');
                $table->string('extraction_pattern', 127)->default('')
                      ->nullable()->comment('提取正则');
                $table->unsignedBigInteger('creator_id')->default(0)
                      ->comment('创建人ID');
                $table->unsignedBigInteger('updater_id')->default(0)
                      ->comment('更新人ID');
                $table->dateTime('created_at')->nullable()->comment('创建时间');
                $table->dateTime('updated_at')->nullable()->comment('更新时间');
                $table->dateTime('deleted_at')->nullable()->comment('删除时间');

                // 添加索引
                $table->index('creator_id', 'idx_creator_id');
                $table->index('updater_id', 'idx_updater_id');
            });
        }
    }

    public function down()
    {
        // 只有当表存在时才执行删除
        if (Schema::hasTable('low_code_diseases')) {
            Schema::dropIfExists('low_code_diseases');
        }
    }
}