<?php

use Illuminate\Support\Facades\Route;
use BrightLiu\LowCode\Controllers\LowCode\LowCodePersonalizeModuleController;
use BrightLiu\LowCode\Controllers\LowCode\LowCodeV2ListController;
use BrightLiu\LowCode\Controllers\LowCode\ResidentCrowdController;

Route::group([
], function () {

    Route::prefix('v2/resident')->group(function () {

        Route::get('resident-crowd/optional', [ResidentCrowdController::class, 'optional']);

    });

    // 个性化模板
    Route::prefix('v2/foundation/personalize-module')->group(function () {
        Route::get('list', [LowCodePersonalizeModuleController::class, 'list']);
        Route::get('routes', [LowCodePersonalizeModuleController::class, 'routes']);
        Route::post('save', [LowCodePersonalizeModuleController::class, 'save']);
    });

    // 列表v2版本
    Route::prefix('v2/low-code/list')->group(function () {
        Route::get('simple-list', [LowCodeV2ListController::class, 'simpleList']);
        Route::post('query', [LowCodeV2ListController::class, 'query']);
        Route::post('pre', [LowCodeV2ListController::class, 'pre']);
        Route::post('query-count', [LowCodeV2ListController::class, 'queryCount']);
        Route::get('optional-columns', [LowCodeV2ListController::class, 'optionalColumns']);
        Route::get('get-column-preference', [LowCodeV2ListController::class, 'getColumnPreference']);
        Route::post('update-column-preference', [LowCodeV2ListController::class, 'updateColumnPreference']);
    });
});
