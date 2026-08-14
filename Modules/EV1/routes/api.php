<?php

use Illuminate\Support\Facades\Route;
use Modules\EV1\Http\Controllers\EV1Controller;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('ev1s', EV1Controller::class)->names('ev1');
});
