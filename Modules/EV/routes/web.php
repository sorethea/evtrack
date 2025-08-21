<?php

use Illuminate\Support\Facades\Route;
use Modules\EV\Http\Controllers\EVController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('evs', EVController::class)->names('ev');
    Route::get('/evlogs/{id}/analyse', [\Modules\EV\Filament\Resources\EvLogResource\Pages\AnalyseEvLog::class,'analyse'])->name('evlogs.analyse');
});
