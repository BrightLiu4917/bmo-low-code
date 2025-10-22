<?php

declare(strict_types=1);

use BrightLiu\LowCode\Controllers\Resident\ResidentMetricController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v2/resident', 'middleware' => ['bmp.disease.auth']], function () {
    Route::get('resident-metric/optional', [ResidentMetricController::class, 'optional'])
        ->comment('居民-居民指标:可选指标');

    Route::get('resident-metric/monitor-list', [ResidentMetricController::class, 'monitorList'])
        ->comment('居民-居民指标:监测指标列表');

    Route::get('resident-metric/monitor-trend-items', [ResidentMetricController::class, 'monitorTrendItems'])
        ->comment('居民-居民指标:监测指标趋势');

    Route::post('resident-metric/save-monitor', [ResidentMetricController::class, 'saveMonitor'])
        ->comment('居民-居民指标:保存监测指标项');
});
