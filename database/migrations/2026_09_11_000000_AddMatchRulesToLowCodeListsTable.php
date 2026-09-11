<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMatchRulesToLowCodeListsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('low_code_lists') && !Schema::hasColumn('low_code_lists', 'match_rules')) {
            Schema::table('low_code_lists', function (Blueprint $table) {
                $table->json('match_rules')->nullable()->comment('匹配规则')->after('route_group');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('low_code_lists') && Schema::hasColumn('low_code_lists', 'match_rules')) {
            Schema::table('low_code_lists', function (Blueprint $table) {
                $table->dropColumn('match_rules');
            });
        }
    }
}
