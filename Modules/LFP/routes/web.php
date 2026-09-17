<?php

use Illuminate\Support\Facades\Route;
use Modules\LFP\Http\Controllers\LFPController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('lfps', LFPController::class)->names('lfp');
});
