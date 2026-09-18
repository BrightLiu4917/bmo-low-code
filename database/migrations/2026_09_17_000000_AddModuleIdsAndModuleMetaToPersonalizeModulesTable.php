<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddModuleIdsAndModuleMetaToPersonalizeModulesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('personalize_modules')) {
            return;
        }

        if (!Schema::hasColumn('personalize_modules', 'module_ids')) {
            Schema::table('personalize_modules', function (Blueprint $table) {
                $table->json('module_ids')->nullable()->comment('关联人群ID列表')->after('module_id');
            });
        }

        if (!Schema::hasColumn('personalize_modules', 'module_meta')) {
            Schema::table('personalize_modules', function (Blueprint $table) {
                $table->json('module_meta')->nullable()->comment('模块额外信息')->after('module_ids');
            });
        }
    }

    public function down()
    {
        if (!Schema::hasTable('personalize_modules')) {
            return;
        }

        Schema::table('personalize_modules', function (Blueprint $table) {
            if (Schema::hasColumn('personalize_modules', 'module_meta')) {
                $table->dropColumn('module_meta');
            }
            if (Schema::hasColumn('personalize_modules', 'module_ids')) {
                $table->dropColumn('module_ids');
            }
        });
    }
}
