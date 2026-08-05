<?php

use Illuminate\Support\Facades\Route;
use Modules\EV5\Http\Controllers\EV5Controller;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('ev5s', EV5Controller::class)->names('ev5');
});
