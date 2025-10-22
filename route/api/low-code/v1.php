<?php

use Illuminate\Support\Facades\Route;
use BrightLiu\LowCode\Controllers\LowCode\LowCodeListController;
use BrightLiu\LowCode\Controllers\LowCode\LowCodeTemplateController;
use BrightLiu\LowCode\Controllers\Disease\DiseaseController;
use BrightLiu\LowCode\Controllers\DatabaseSource\DatabaseSourceController;

//病种
Route::prefix('v1/disease')->group(function () {
    Route::get('list', [DiseaseController::class, 'list']);
    Route::get('show', [DiseaseController::class, 'show']);
    Route::post('update', [DiseaseController::class, 'update']);
    Route::post('delete', [DiseaseController::class, 'delete']);
});

//数据源
Route::prefix('v1/database-source')->group(function () {
    Route::get('list', [DatabaseSourceController::class, 'list']);
    Route::get('show', [DatabaseSourceController::class, 'show']);
    Route::post('update', [DatabaseSourceController::class, 'update']);
    Route::post('delete', [DatabaseSourceController::class, 'delete']);
});


    Route::prefix('v1/low-code/list')->group(function () {
        Route::get('list', [LowCodeListController::class, 'list']);
        Route::get('show', [LowCodeListController::class, 'show']);
        Route::post('simple-list', [LowCodeListController::class, 'simpleList']);
        Route::post('update', [LowCodeListController::class, 'update']);
        Route::post('delete', [LowCodeListController::class, 'delete']);
        Route::post('query', [LowCodeListController::class, 'query']);
        Route::post('pre', [LowCodeListController::class, 'pre']);
    });

    Route::prefix('v1/low-code/template')->group(function () {
        Route::get('list', [LowCodeTemplateController::class, 'list']);
        Route::get('show', [LowCodeTemplateController::class, 'show']);
        Route::post('update', [LowCodeTemplateController::class, 'update']);
        Route::post('delete', [LowCodeTemplateController::class, 'delete']);
    });
    Route::post('v1/low-code/template-bind-parts', [LowCodeTemplateController::class, 'bindPart']);

