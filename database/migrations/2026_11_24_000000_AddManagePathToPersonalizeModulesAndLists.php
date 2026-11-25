<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddManagePathToPersonalizeModulesAndLists extends Migration
{
    public function up()
    {
        if (Schema::hasTable('personalize_modules')) {
            if (!Schema::hasColumn('personalize_modules', 'sub_module_ids')) {
                $after = Schema::hasColumn('personalize_modules', 'module_meta') ? 'module_meta' : 'module_id';
                Schema::table('personalize_modules', function (Blueprint $table) use ($after) {
                    $table->json('sub_module_ids')->nullable()->comment('关联管理方案ID列表')->after($after);
                });
            }
        }

        if (Schema::hasTable('low_code_lists') && !Schema::hasColumn('low_code_lists', 'query_scope')) {
            Schema::table('low_code_lists', function (Blueprint $table) {
                $table->string('query_scope', 32)->default('')->comment('查询范围:crowd人群,manage_path管理路径,hidden隐藏')->after('list_type');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('personalize_modules')) {
            Schema::table('personalize_modules', function (Blueprint $table) {
                if (Schema::hasColumn('personalize_modules', 'sub_module_ids')) {
                    $table->dropColumn('sub_module_ids');
                }
            });
        }

        if (Schema::hasTable('low_code_lists') && Schema::hasColumn('low_code_lists', 'query_scope')) {
            Schema::table('low_code_lists', function (Blueprint $table) {
                $table->dropColumn('query_scope');
            });
        }
    }
}
