<?php

use Illuminate\Support\Facades\Route;

// Serve the React SPA for all routes
Route::get('/{any?}', function () {
    return view('spa');
})->where('any', '.*');
