<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResidentMonitorMetricsTable extends Migration
{
    public function up()
    {
        Schema::create('resident_monitor_metrics', function (Blueprint $table) {
            $table->id()->comment('主键');
            $table->string('disease_code', 32)->default('')->comment('疾病编码');
            $table->string('resident_empi', 32)->default('')->comment('居民empi');
            $table->string('metric_title', 64)->default('')->comment('指标标题');
            $table->string('metric_id', 64)->default('')->comment('指标ID');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
        });
    }

    public function down()
    {
        Schema::dropIfExists('resident_monitor_metrics');
    }
}