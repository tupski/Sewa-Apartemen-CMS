<?php

use App\Http\Controllers\InstallerController;
use Illuminate\Support\Facades\Route;

Route::prefix('install')->middleware('web')->group(function () {
    Route::get('/', [InstallerController::class, 'index'])->name('install');

    Route::get('/step/{step}', [InstallerController::class, 'step'])->name('install.step');
    Route::post('/step/{step}', [InstallerController::class, 'processStep'])->name('install.process');

    // Requirements check endpoint
    Route::post('/requirements', [InstallerController::class, 'step1'])->name('install.requirements');

    // Application config endpoint
    Route::post('/application', [InstallerController::class, 'step2'])->name('install.application');

    // Database config endpoints
    Route::post('/database/test-connection', [InstallerController::class, 'testDatabaseConnection'])->name('install.database.test');
    Route::post('/database', [InstallerController::class, 'step3'])->name('install.database');

    // Admin creation endpoint
    Route::post('/admin', [InstallerController::class, 'step4'])->name('install.admin');

    // Website config endpoint
    Route::post('/website', [InstallerController::class, 'step5'])->name('install.website');

    // Finish endpoint
    Route::post('/finish', [InstallerController::class, 'step6'])->name('install.finish');
});
