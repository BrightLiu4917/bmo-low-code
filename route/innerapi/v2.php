<?php

use Illuminate\Support\Facades\Route;
use BrightLiu\LowCode\Controllers\LowCode\InitOrgDiseaseController;

Route::prefix('v2')->group(function() {
    Route::get('init/org-disease', [InitOrgDiseaseController::class,'__invoke'])
    ->middleware('bmp.disease.auth.inner');
});

