<?php

declare(strict_types=1);

use BrightLiu\LowCode\Controllers\Callback\ManageStatusNotifyController;
use Illuminate\Support\Facades\Route;

Route::prefix('callback')->group(function () {
    Route::any('manage-status-notify', ManageStatusNotifyController::class)
        ->comment('纳管状态变更通知');
});
