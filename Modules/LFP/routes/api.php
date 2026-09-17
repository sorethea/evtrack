<?php

use Illuminate\Support\Facades\Route;
use Modules\LFP\Http\Controllers\LFPController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('lfps', LFPController::class)->names('lfp');
});
