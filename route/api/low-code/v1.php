<?php

use Illuminate\Support\Facades\Route;
use BrightLiu\LowCode\Controllers\LowCode\LowCodeListController;
use BrightLiu\LowCode\Controllers\LowCode\LowCodeTemplateController;
use BrightLiu\LowCode\Controllers\Disease\DiseaseController;
use BrightLiu\LowCode\Controllers\DatabaseSource\DatabaseSourceController;

//病种
Route::prefix('v1/disease')->group(function () {
    Route::get('list', [DiseaseController::class, 'list'])->comment('病种:列表');
    Route::get('show', [DiseaseController::class, 'show'])->comment('病种:详情');
    Route::post('update', [DiseaseController::class, 'update'])->comment('病种:更新');
    Route::post('delete', [DiseaseController::class, 'delete'])->comment('病种:删除');
});

//数据源
Route::prefix('v1/database-source')->group(function () {
    Route::get('list', [DatabaseSourceController::class, 'list'])->comment('数据源:列表');
    Route::get('show', [DatabaseSourceController::class, 'show'])->comment('数据源:详情');
    Route::post('update', [DatabaseSourceController::class, 'update'])->comment('数据源:更新');
    Route::post('delete', [DatabaseSourceController::class, 'delete'])->comment('数据源:删除');
});


    Route::prefix('v1/low-code/list')->group(function () {
        Route::get('list', [LowCodeListController::class, 'list'])->comment('低代码-列表:咸列表');
        Route::get('show', [LowCodeListController::class, 'show'])->comment('低代码-列表:详情');
        Route::post('simple-list', [LowCodeListController::class, 'simpleList'])->comment('低代码-列表:简单列表');
        Route::post('update', [LowCodeListController::class, 'update'])->comment('低代码-列表:更新');
        Route::post('delete', [LowCodeListController::class, 'delete'])->comment('低代码-列表:删除');
        Route::post('query', [LowCodeListController::class, 'query'])->comment('低代码-列表:查询请求');
        Route::post('pre', [LowCodeListController::class, 'pre'])->comment('低代码-列表:预请求');
    });

    Route::prefix('v1/low-code/template')->group(function () {
        Route::get('list', [LowCodeTemplateController::class, 'list'])->comment('低代码-模板:列表');
        Route::get('show', [LowCodeTemplateController::class, 'show'])->comment('低代码-模板:详情');
        Route::post('update', [LowCodeTemplateController::class, 'update'])->comment('低代码-模板:更新');
        Route::post('delete', [LowCodeTemplateController::class, 'delete'])->comment('低代码-模板:删除');
    });
    Route::post('v1/low-code/template-bind-parts', [LowCodeTemplateController::class, 'bindPart'])->comment('低代码-模板:绑定零件');

