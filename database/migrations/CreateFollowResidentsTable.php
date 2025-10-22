<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFollowResidentsTable extends Migration
{
    public function up()
    {
        Schema::create('follow_residents', function (Blueprint $table) {
            $table->id()->comment('主键');
            $table->string('disease_code', 32)->default('')->comment('疾病编码');
            $table->unsignedBigInteger('admin_id')->default(0)->comment('管理员ID');
            $table->string('resident_user_id', 32)->default('')->comment('居民user_id');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
        });
    }

    public function down()
    {
        Schema::dropIfExists('follow_residents');
    }
}