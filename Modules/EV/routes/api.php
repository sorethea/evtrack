<?php

use Illuminate\Support\Facades\Route;
use Modules\EV\Http\Controllers\EVController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('evs', EVController::class)->names('ev');
});
