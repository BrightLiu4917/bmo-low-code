<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLowCodeListsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('low_code_lists')) {
            Schema::create('low_code_lists', function (Blueprint $table) {
                // 主键和基础字段
                $table->bigIncrements('id')->comment('主键');
                $table->string('data_permission_code', 64)->default('')->comment('数据权限编码');
                $table->string('disease_code', 32)->default('')->comment('病种编码');
                $table->string('code', 64)->default('')->unique()->comment('列表编码');
                $table->string('parent_code', 64)->default('')->comment('上级列表编码');
                $table->string('org_code', 64)->default('')->comment('机构编码');

                // 名称字段
                $table->string('admin_name', 64)->default('')->comment('专病后台列表名字');
                $table->string('family_doctor_name', 64)->default('')->comment('家庭医生列表名字');
                $table->string('mobile_doctor_name', 64)->default('')->comment('移动医生列表名字');

                $table->string('crowd_type_code', 64)->default('')->comment('人群分类编码');

                // 权重字段
                $table->unsignedInteger('admin_weight')->default(0)->comment('专病后台权重(降序)');
                $table->unsignedInteger('family_doctor_weight')->default(0)->comment('家庭医生权重(降序)');
                $table->unsignedInteger('mobile_doctor_weight')->default(0)->comment('移动医生权重(降序)');

                // 模板相关字段
                $table->string('template_code_filter', 64)->default('')->comment('模板-筛选');
                $table->string('template_code_column', 64)->default('')->comment('模板-表头');
                $table->string('template_code_field', 64)->default('')->comment('模板-查询字段');
                $table->string('template_code_button', 64)->default('')->comment('模板-操作按钮');
                $table->string('template_code_top_button', 64)->default('')->comment('模板-顶部操作按钮');

                // JSON 字段
                $table->json('route_group')->nullable()->comment('前端路由组');
                $table->json('append_field_json')->nullable()->comment('追加查询字段，模板少了');
                $table->json('append_column_json')->nullable()->comment('追加表头，模板少了');
                $table->json('append_filter_json')->nullable()->comment('追加筛选条件，模板少了');
                $table->json('append_button_json')->nullable()->comment('追加按钮，模板少了');
                $table->json('append_top_button_json')->nullable()->comment('追加顶部按钮，模板少了');
                $table->json('remove_field_json')->nullable()->comment('移除查询字段，模板多了');
                $table->json('remove_filter_json')->nullable()->comment('移除筛选条件，模板多了');
                $table->json('remove_column_json')->nullable()->comment('移除表头，模板多了');
                $table->json('remove_button_json')->nullable()->comment('移除筛选条件，模板多了');
                $table->json('remove_top_button_json')->nullable()->comment('移除表头，模板多了');
                $table->json('default_order_by_json')->nullable()->comment('默认排序字段');
                $table->json('preset_condition_json')->nullable()->comment('预设条件');

                // 其他字段
                $table->unsignedTinyInteger('list_type')->default(0)->comment('列表类型0默认，9通用（适配多个人群分类）不可删除');
                $table->unsignedBigInteger('creator_id')->default(0)->comment('创建人ID');
                $table->unsignedBigInteger('updater_id')->default(0)->comment('更新人ID');
                $table->dateTime('created_at')->nullable()->comment('创建时间');
                $table->dateTime('updated_at')->nullable()->comment('更新时间');
                $table->dateTime('deleted_at')->nullable()->comment('删除时间');

                // 索引
                $table->index('disease_code', 'idx_disease_code');
                $table->index('org_code', 'idx_org_code');
                $table->index('creator_id', 'idx_creator_id');
                $table->index('updater_id', 'idx_updater_id');
                $table->index('template_code_filter', 'idx_template_code_filter');
                $table->index('template_code_field', 'idx_template_code_field');
                $table->index('template_code_column', 'idx_template_code_column');
                $table->index('template_code_button', 'template_code_button');
                $table->index('template_code_top_button', 'idx_template_code_top_button');
                $table->index(['disease_code', 'org_code'], 'idx_disease_code_org_code');
                $table->index('parent_code', 'idx_partent_code');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('low_code_lists')) {
            Schema::dropIfExists('low_code_lists');
        }
    }
}