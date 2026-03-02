<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApiAccessLogTable extends Migration
{
    public function up()
    {
        Schema::create('api_access_logs', function (Blueprint $table) {
            $table->id()->comment('主键');
            $table->json('request_params')->nullable()->comment('请求参数');
            $table->string('request_ip', 64)->default('')->comment('请求IP');
            $table->string('request_route', 128)->default('')->comment('请求路由');
            $table->string('process_time_format', 32)->default('')->comment('处理耗时(秒)');
            $table->unsignedBigInteger('process_time')->default(0)->comment('处理耗时(毫秒)');
            $table->longText('process_exception')->nullable()->comment('处理异常信息');
            $table->json('response_data')->nullable()->comment('响应数据');
            $table->dateTime('created_at')->nullable()->comment('创建时间(请求时间)');

            $table->comment('接口访问日志表');
        });
    }

    public function down()
    {
        Schema::dropIfExists('api_access_logs');
    }
}