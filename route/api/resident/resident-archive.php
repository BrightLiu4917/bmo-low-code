<?php

declare(strict_types=1);

use BrightLiu\LowCode\Controllers\Resident\ResidentArchiveController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v2/resident', 'middleware' => ['bmp.disease.auth']], function () {
    Route::get('resident-archive/basic-info', [ResidentArchiveController::class, 'basicInfo'])
        ->comment('居民-居民档案:基本信息');

    Route::post('resident-archive/follow', [ResidentArchiveController::class, 'follow'])
        ->comment('居民-居民档案:重点关注');

    Route::post('resident-archive/unfollow', [ResidentArchiveController::class, 'unfollow'])
        ->comment('居民-居民档案:取消重点关注');

    Route::post('resident-archive/mask-testing', [ResidentArchiveController::class, 'maskTesting'])
        ->comment('居民-居民档案:标记为测试');

    Route::post('resident-archive/unmask-testing', [ResidentArchiveController::class, 'unmaskTesting'])
        ->comment('居民-居民档案:取消测试标记');

    Route::get('resident-archive/info', [ResidentArchiveController::class, 'info'])
        ->comment('居民-居民档案:健康档案信息');

    Route::post('resident-archive/update-info', [ResidentArchiveController::class, 'updateInfo'])
        ->comment('居民-居民档案:更新健康档案信息');
});
