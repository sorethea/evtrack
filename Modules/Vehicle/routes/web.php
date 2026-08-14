<?php

use Illuminate\Support\Facades\Route;
use Modules\Vehicle\Http\Controllers\VehicleController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('vehicles', VehicleController::class)->names('vehicle');
});
