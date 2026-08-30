<?php

use Illuminate\Support\Facades\Route;
use Modules\SA\Http\Controllers\SAController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('sas', SAController::class)->names('sa');
});
