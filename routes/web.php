<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UploadController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Middleware\Cors;
use Illuminate\Support\Facades\Log;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/uploadFiles', [UploadController::class, 'uploadFiles'])->name('uploadFiles');
});
//Route::group(['middleware' => 'cors'], function () {
Route::post('/upload', [UploadController::class, 'upload'])->name('upload')->middleware(Cors::class);
//});

// Test
Route::get('/log-test', function () {
    Log::info('This is a test log message.');
    return 'Log message has been written!';
});

require __DIR__.'/auth.php';
