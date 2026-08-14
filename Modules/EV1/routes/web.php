<?php

use Illuminate\Support\Facades\Route;
use Modules\EV1\Http\Controllers\EV1Controller;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('ev1s', EV1Controller::class)->names('ev1');
});
