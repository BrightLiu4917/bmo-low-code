<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use BrightLiu\LowCode\Controllers\Resident\ResidentMaintenanceController;
use BrightLiu\LowCode\Enums\Foundation\Middleware;

Route::group(['prefix' => 'v2/resident', 'middleware' => [Middleware::AUTH_DISEASE]], function () {
    Route::post('resident-maintenance/create', [ResidentMaintenanceController::class, 'create'])
        ->comment('居民-居民维护:新增');

    Route::post('resident-maintenance/import', [ResidentMaintenanceController::class, 'import'])
        ->comment('居民-居民维护:导入');

    Route::post('resident-maintenance/get-import-template', [ResidentMaintenanceController::class, 'getImportTemplate'])
        ->comment('居民-居民维护:获取导入模板');
});
