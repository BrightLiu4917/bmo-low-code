<?php

use Illuminate\Support\Facades\Route;
use BrightLiu\LowCode\Controllers\LowCode\LowCodePersonalizeModuleController;
use BrightLiu\LowCode\Controllers\LowCode\LowCodeListV2Controller;
use BrightLiu\LowCode\Controllers\LowCode\ResidentCrowdController;

Route::group([
], function () {

    Route::prefix('v2/resident')->group(function () {

        Route::get('resident-crowd/optional', [ResidentCrowdController::class, 'optional']);

        //患者相关
        Route::any('resident-archive/basic-info', [LowCodeListV2Controller::class, 'basicInfo']);
    });

    // 个性化模板
    Route::prefix('v2/foundation/personalize-module')->group(function () {
        Route::get('list', [LowCodePersonalizeModuleController::class, 'list']);
        Route::get('routes', [LowCodePersonalizeModuleController::class, 'routes']);
        Route::post('save', [LowCodePersonalizeModuleController::class, 'save']);
    });

    // 列表v2版本
    Route::prefix('v2/low-code/list')->group(function () {
        Route::get('simple-list', [LowCodeListV2Controller::class, 'simpleList']);
        Route::post('query', [LowCodeListV2Controller::class, 'query']);
        Route::post('pre', [LowCodeListV2Controller::class, 'pre']);
        Route::post('query-count', [LowCodeListV2Controller::class, 'queryCount']);
        Route::get('optional-columns', [LowCodeListV2Controller::class, 'optionalColumns']);
        Route::get('get-column-preference', [LowCodeListV2Controller::class, 'getColumnPreference']);
        Route::post('update-column-preference', [LowCodeListV2Controller::class, 'updateColumnPreference']);
    });
});
