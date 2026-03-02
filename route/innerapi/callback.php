<?php

declare(strict_types=1);

use BrightLiu\LowCode\Controllers\Callback\ManageStatusNotifyController;
use BrightLiu\LowCode\Middleware\ApiAccessVia;
use Illuminate\Support\Facades\Route;

Route::prefix('callback')->group(function () {
    Route::any('manage-status-notify', ManageStatusNotifyController::class)
        ->middleware([ApiAccessVia::withParams()])
        ->comment('纳管状态变更通知');
});
