<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\EditorUploadController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MediaCategoryController;

Route::get('/', fn() => view('welcome'))->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->as('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('ckeditor/upload', [EditorUploadController::class, 'upload'])->name('ckeditor.upload');

    // Posts…
    Route::get('posts/create/{type?}', [PostController::class, 'create'])
        ->where('type', 'post|page|news|blog|product')
        ->name('posts.create');

    Route::post('posts/{type?}', [PostController::class, 'store'])
        ->where('type', 'post|page|news|blog|product')
        ->name('posts.store');

    Route::get('posts/{post}/edit/{type?}', [PostController::class, 'edit'])
        ->whereNumber('post')
        ->where('type', 'post|page|news|blog|product')
        ->name('posts.edit');

    Route::patch('posts/{post}/{type?}', [PostController::class, 'update'])
        ->whereNumber('post')
        ->where('type', 'post|page|news|blog|product')
        ->name('posts.update');

    Route::delete('posts/{post}/{type?}', [PostController::class, 'destroy'])
        ->whereNumber('post')
        ->where('type', 'post|page|news|blog|product')
        ->name('posts.destroy');

    // Keep index last so it doesn't shadow the above routes
    Route::get('posts/{type?}', [PostController::class, 'index'])
        ->where('type', 'post|page|news|blog|product')
        ->name('posts.index');

    // Categories…
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('categories/{term}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::patch('categories/{term}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{term}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Media Library
    Route::prefix('media')->name('media.')->group(function () {
        Route::get('/', [MediaController::class, 'index'])->name('index');
        Route::get('/list', [MediaController::class, 'list'])->name('list');
        Route::post('/upload', [MediaController::class, 'upload'])->name('upload');
        Route::patch('/meta/{media}', [MediaController::class, 'updateMeta'])->name('meta');
        Route::patch('/move/{media}', [MediaController::class, 'moveCategory'])->name('move');
        Route::post('/replace/{media}', [MediaController::class, 'replaceFile'])->name('replace');
        Route::delete('/{media}', [MediaController::class, 'destroy'])->name('destroy');
        Route::post('/restore/{id}', [MediaController::class, 'restore'])->name('restore');
        Route::delete('/force/{id}', [MediaController::class, 'forceDelete'])->name('force');

        // ✅ NEW: bulk actions for Media Library
        Route::post('/bulk-delete', [MediaController::class, 'bulkDelete'])->name('bulk-delete');        // soft delete (to trash)
        Route::post('/bulk-restore', [MediaController::class, 'bulkRestore'])->name('bulk-restore');      // restore from trash
        Route::post('/bulk-force-delete', [MediaController::class, 'bulkForceDelete'])->name('bulk-force');    // permanent delete

        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [MediaCategoryController::class, 'index'])->name('index');
            Route::get('/create', [MediaCategoryController::class, 'create'])->name('create');
            Route::post('/', [MediaCategoryController::class, 'store'])->name('store');
            Route::get('/{tt}/edit', [MediaCategoryController::class, 'edit'])->name('edit');
            Route::patch('/{tt}', [MediaCategoryController::class, 'update'])->name('update');
            Route::delete('/{tt}', [MediaCategoryController::class, 'destroy'])->name('destroy');

            // AJAX helpers
            Route::get('/json', [MediaCategoryController::class, 'json'])->name('json');
            Route::post('/quick', [MediaCategoryController::class, 'quickStore'])->name('quick');
            Route::post('/quick-create', [MediaCategoryController::class, 'quickStore']); // alias
        });
    });
});

require __DIR__ . '/auth.php';