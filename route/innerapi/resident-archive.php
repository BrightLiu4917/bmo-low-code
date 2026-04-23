<?php

declare(strict_types=1);

use BrightLiu\LowCode\Controllers\Resident\ResidentArchiveController;
use BrightLiu\LowCode\Enums\Foundation\Middleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v2/resident', 'middleware' => [Middleware::AUTH_DISEASE_INNER]], function () {
    Route::get('resident-archive/info', [ResidentArchiveController::class, 'info'])
        ->comment('居民-居民档案:健康档案信息');

    Route::get('resident-archive/basic-info', [ResidentArchiveController::class, 'basicInfo'])
        ->comment('居民-居民档案:基本信息');

    Route::post('resident-archive/update-info', [ResidentArchiveController::class, 'updateInfo'])
        ->comment('居民-居民档案:更新健康档案信息');
});
