<?php

use Illuminate\Support\Facades\Route;
use Modules\EV5\Http\Controllers\EV5Controller;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('ev5s', EV5Controller::class)->names('ev5');
});
