<?php

declare(strict_types=1);

use BrightLiu\LowCode\Controllers\Resident\ResidentManagementController;
use BrightLiu\LowCode\Enums\Foundation\Middleware;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v2/resident', 'middleware' => [Middleware::AUTH_DISEASE]], function () {
    Route::post('resident-management/pre', [ResidentManagementController::class, 'pre'])
        ->comment('居民-居民纳管:前置校验');
});
