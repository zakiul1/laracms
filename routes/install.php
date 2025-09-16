<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Installer\InstallController;

Route::middleware(['web'])->group(function () {
    Route::prefix('install')->name('install.')->group(function () {
        Route::get('/', [InstallController::class, 'welcome'])->name('welcome');

        Route::get('/requirements', [InstallController::class, 'requirements'])->name('requirements');
        Route::post('/requirements', [InstallController::class, 'requirementsNext'])->name('requirements.next');

        Route::get('/database', [InstallController::class, 'database'])->name('database');
        Route::post('/database', [InstallController::class, 'databaseSave'])->name('database.save');
        Route::post('/database/test', [InstallController::class, 'databaseTest'])->name('database.test');

        Route::get('/admin', [InstallController::class, 'admin'])->name('admin');
        Route::post('/admin', [InstallController::class, 'adminSave'])->name('admin.save');

        Route::get('/site', [InstallController::class, 'site'])->name('site');
        Route::post('/site', [InstallController::class, 'siteSave'])->name('site.save');

        Route::get('/finish', [InstallController::class, 'finish'])->name('finish');
        Route::post('/finish', [InstallController::class, 'finishRun'])->name('finish.run');
    });
});