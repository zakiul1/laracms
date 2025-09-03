<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\EditorUploadController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MediaCategoryController;
// Plugin Manager
use App\Http\Controllers\Admin\PluginController;
// ⬇️ NEW: Menus module controllers
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\Admin\MenuLocationController;

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

        // ✅ NEW: detail JSON for sidebar in Media Browser
        Route::get('/show/{media}', [MediaController::class, 'show'])->name('show');

        Route::delete('/{media}', [MediaController::class, 'destroy'])->name('destroy');

        // ✅ NEW (optional): friendly alias for permanent delete used by the modal
        Route::delete('/delete/{media}', [MediaController::class, 'destroy'])->name('delete');

        Route::post('/restore/{id}', [MediaController::class, 'restore'])->name('restore');
        Route::delete('/force/{id}', [MediaController::class, 'forceDelete'])->name('force');

        // Bulk actions
        Route::post('/bulk-delete', [MediaController::class, 'bulkDelete'])->name('bulk-delete');
        Route::post('/bulk-restore', [MediaController::class, 'bulkRestore'])->name('bulk-restore');
        Route::post('/bulk-force-delete', [MediaController::class, 'bulkForceDelete'])->name('bulk-force');

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

    // =========================
    // Plugin Manager
    // =========================
    Route::prefix('plugins')->name('plugins.')->group(function () {
        Route::get('/', [PluginController::class, 'index'])->name('index');
        Route::post('/sync', [PluginController::class, 'sync'])->name('sync');

        // Upload & install
        Route::get('/upload', fn() => view('admin.plugins.upload'))->name('upload.form');
        Route::post('/upload', [PluginController::class, 'upload'])->name('upload');

        // Activate / Deactivate
        Route::post('/{plugin}/activate', [PluginController::class, 'activate'])->name('activate');
        Route::post('/{plugin}/deactivate', [PluginController::class, 'deactivate'])->name('deactivate');

        // Update
        Route::post('/{plugin}/update/upload', [PluginController::class, 'updateUpload'])->name('update.upload');
        Route::post('/{plugin}/update/remote', [PluginController::class, 'updateRemote'])->name('update.remote');

        // Settings
        Route::get('/{plugin}/settings', [PluginController::class, 'settings'])->name('settings');
        Route::post('/{plugin}/settings', [PluginController::class, 'saveSettings'])->name('settings.save');

        // Export / Delete
        Route::get('/{plugin}/export', [PluginController::class, 'export'])->name('export');
        Route::delete('/{plugin}', [PluginController::class, 'destroy'])->name('destroy');
    });

    // =========================
    // Menus (NEW)
    // =========================
    Route::prefix('menus')->name('menus.')->group(function () {
        // Menus CRUD
        Route::get('/', [MenuController::class, 'index'])->name('index');
        Route::post('/', [MenuController::class, 'store'])->name('store');
        Route::get('/{menu}/edit', [MenuController::class, 'edit'])->name('edit');
        Route::patch('/{menu}', [MenuController::class, 'update'])->name('update');
        Route::delete('/{menu}', [MenuController::class, 'destroy'])->name('destroy');

        // Drag & drop reorder (nested tree JSON)
        Route::post('/{menu}/reorder', [MenuController::class, 'reorder'])->name('reorder');

        // Menu items
        Route::post('/{menu}/items/custom', [MenuItemController::class, 'storeCustom'])->name('items.custom.store');
        Route::post('/{menu}/items/bulk', [MenuItemController::class, 'storeBulk'])->name('items.bulk.store');
        Route::patch('/{menu}/items/{item}', [MenuItemController::class, 'update'])->name('items.update');
        Route::delete('/{menu}/items/{item}', [MenuItemController::class, 'destroy'])->name('items.destroy');

        // Assign locations from the menu edit screen
        Route::post('/{menu}/assign-locations', [MenuController::class, 'assignLocations'])->name('assign');

        // Locations screen
        Route::get('/locations', [MenuLocationController::class, 'index'])->name('locations.index');
        Route::post('/locations', [MenuLocationController::class, 'update'])->name('locations.update');
    });
});

require __DIR__ . '/auth.php';