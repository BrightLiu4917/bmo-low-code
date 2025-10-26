<?php

use Illuminate\Support\Facades\Route;
use BrightLiu\LowCode\Controllers\LowCode\InitOrgDiseaseController;
use BrightLiu\LowCode\Enums\Foundation\Middleware;

Route::prefix('v2')->group(function() {
    Route::any('init/org-disease', InitOrgDiseaseController::class)
    ->middleware(Middleware::AUTH_DISEASE_INNER);
});

