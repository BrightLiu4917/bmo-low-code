<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLowCodeTemplatesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('low_code_templates')) {
            Schema::create('low_code_templates', function (Blueprint $table) {
                // 主键和基础字段
                $table->bigIncrements('id')->comment('主键');
                $table->string('name', 64)->default('')->comment('名字');
                $table->string('disease_code', 64)->default('')->comment('疾病编码');
                $table->string('code', 64)->default('')->unique()->comment('唯一编码');
                $table->string('org_code', 64)->default('')->comment('机构编码');

                // 类型字段
                $table->unsignedTinyInteger('template_type')->default(0)->comment('模板类型:1通用的已纳管,2通用的待纳管,3通用的推荐纳管,4通用的出组');
                $table->unsignedTinyInteger('content_type')->default(0)->comment('内容类型:1列头展示，2筛选项，3操作栏按钮，4顶部按钮，5查询字段集合');

                // 描述和权重
                $table->string('description', 200)->default('')->comment('描述');
                $table->unsignedInteger('weight')->default(0)->comment('权重(降序)');

                // 操作人字段
                $table->unsignedBigInteger('creator_id')->default(0)->comment('创建人ID');
                $table->unsignedBigInteger('updater_id')->default(0)->comment('更新人ID');

                // 时间戳字段
                $table->dateTime('created_at')->nullable()->comment('创建时间');
                $table->dateTime('updated_at')->nullable()->comment('更新时间');
                $table->dateTime('deleted_at')->nullable()->comment('删除时间');

                // 索引
                $table->index('content_type', 'idx_content_type');
                $table->index('disease_code', 'idx_disease_code');
                $table->index('org_code', 'idx_org_code');
                $table->index(['disease_code', 'org_code'], 'idx_disease_code_org_code');
                $table->index('template_type', 'idx_template_type');
                $table->index('creator_id', 'idx_creator_id');
                $table->index('updater_id', 'idx_updater_id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('low_code_templates')) {
            Schema::dropIfExists('low_code_templates');
        }
    }
}