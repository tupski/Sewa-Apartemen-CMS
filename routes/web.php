<?php

use App\Http\Controllers\BlockController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\NavigationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin CMS Routes (require authentication)
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {

    // Media Management
    Route::resource('media', MediaController::class);
    Route::post('media/upload', [MediaController::class, 'upload'])->name('media.upload');

    // Pages Management
    Route::resource('pages', PageController::class);
    Route::patch('pages/{page}/status', [PageController::class, 'updateStatus'])->name('pages.status');

    // Blocks Management
    Route::resource('blocks', BlockController::class);
    Route::post('blocks/reorder', [BlockController::class, 'reorder'])->name('blocks.reorder');
    Route::patch('blocks/{block}/status', [BlockController::class, 'updateStatus'])->name('blocks.status');

    // Navigation Management
    Route::resource('navigations', NavigationController::class);
    Route::post('navigations/reorder', [NavigationController::class, 'reorder'])->name('navigations.reorder');
    Route::patch('navigations/{navigation}/status', [NavigationController::class, 'updateStatus'])->name('navigations.status');

    // Settings Management
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
});

require __DIR__.'/auth.php';
