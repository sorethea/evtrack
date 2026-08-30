<?php

use Illuminate\Support\Facades\Route;
use Modules\SA\Http\Controllers\SAController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('sas', SAController::class)->names('sa');
});
