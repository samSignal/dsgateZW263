<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::middleware('guest')->group(function () {
    Route::get('/applications/create', [ApplicationController::class, 'create'])->name('applications.create');
    Route::post('/applications', [ApplicationController::class, 'store'])->name('applications.store');
    Route::post('/applications/draft', [ApplicationController::class, 'draft'])->name('applications.draft');
    Route::get('/applications/resume', [ApplicationController::class, 'resumeForm'])->name('applications.resume');
    Route::post('/applications/resume', [ApplicationController::class, 'resume'])->name('applications.resume.submit');
    Route::get('/applications/track', [ApplicationController::class, 'trackForm'])->name('applications.track');
    Route::post('/applications/track', [ApplicationController::class, 'track'])->name('applications.track.submit');
    Route::get('/applications/{application}/success', [ApplicationController::class, 'success'])->name('applications.success');
});

Route::get('/dashboard', function () {
    return view('spa');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Serve the React SPA for all routes
Route::get('/{any?}', function () {
    return view('spa');
})->where('any', '.*');
