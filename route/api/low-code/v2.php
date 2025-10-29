<?php

use Illuminate\Support\Facades\Route;
use BrightLiu\LowCode\Controllers\LowCode\LowCodePersonalizeModuleController;
use BrightLiu\LowCode\Controllers\LowCode\LowCodeV2ListController;
use BrightLiu\LowCode\Controllers\LowCode\ResidentCrowdController;

Route::group([
], function () {

    Route::prefix('v2/resident')->group(function () {

        Route::get('resident-crowd/optional', [ResidentCrowdController::class, 'optional'])->comment('居民-居民人群:可选人群');

    });

    // 个性化模板
    Route::prefix('v2/foundation/personalize-module')->group(function () {
        Route::get('list', [LowCodePersonalizeModuleController::class, 'list'])->comment('基础-个性化模块:列表');
        Route::get('routes', [LowCodePersonalizeModuleController::class, 'routes'])->comment('基础-个性化模块:路由');
        Route::post('save', [LowCodePersonalizeModuleController::class, 'save'])->comment('基础-个性化模块:保存');
    });

    // 列表v2版本
    Route::prefix('v2/low-code/list')->group(function () {
        Route::get('simple-list', [LowCodeV2ListController::class, 'simpleList'])->comment('低代码-列表:简单列表');
        Route::post('query', [LowCodeV2ListController::class, 'query'])->comment('低代码-列表:查询请求');
        Route::post('pre', [LowCodeV2ListController::class, 'pre'])->comment('低代码-列表:预请求');
        Route::post('query-count', [LowCodeV2ListController::class, 'queryCount'])->comment('低代码-列表:查询数量');
        Route::get('optional-columns', [LowCodeV2ListController::class, 'optionalColumns'])->comment('低代码-列表:可选列');
        Route::get('get-column-preference', [LowCodeV2ListController::class, 'getColumnPreference'])->comment('低代码-列表:获取列偏好设置');
        Route::post('update-column-preference', [LowCodeV2ListController::class, 'updateColumnPreference'])->comment('低代码-列表:更新列偏好设置');
    });
});
