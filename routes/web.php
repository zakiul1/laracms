<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\EditorUploadController;

// Media controllers
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MediaCategoryController;

Route::get('/', function () {
    return view('welcome');
})->name('home');

/*
|--------------------------------------------------------------------------
| Authenticated profile settings
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Admin panel (/admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // CKEditor upload endpoint
        Route::post('ckeditor/upload', [EditorUploadController::class, 'upload'])->name('ckeditor.upload');

        /*
        |----------------------------------------------------------------------
        | POSTS (order matters!)
        | /admin/posts/create must come BEFORE /admin/posts/{type?}
        |----------------------------------------------------------------------
        */
        // Create
        Route::get('posts/create/{type?}', [PostController::class, 'create'])
            ->where('type', 'post|page|news|blog|product')
            ->name('posts.create');

        // Store
        Route::post('posts/{type?}', [PostController::class, 'store'])
            ->where('type', 'post|page|news|blog|product')
            ->name('posts.store');

        // Edit (numeric model binding)
        Route::get('posts/{post}/edit/{type?}', [PostController::class, 'edit'])
            ->whereNumber('post')
            ->where('type', 'post|page|news|blog|product')
            ->name('posts.edit');

        // Update (PATCH preferred)
        Route::patch('posts/{post}/{type?}', [PostController::class, 'update'])
            ->whereNumber('post')
            ->where('type', 'post|page|news|blog|product')
            ->name('posts.update');

        // Destroy
        Route::delete('posts/{post}/{type?}', [PostController::class, 'destroy'])
            ->whereNumber('post')
            ->where('type', 'post|page|news|blog|product')
            ->name('posts.destroy');

        // Index (catch-all LAST so it doesn’t swallow /create)
        Route::get('posts/{type?}', [PostController::class, 'index'])
            ->where('type', 'post|page|news|blog|product')
            ->name('posts.index');

        /*
        |----------------------------------------------------------------------
        | CATEGORIES
        |----------------------------------------------------------------------
        */
        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::get('categories/{term}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
        Route::patch('categories/{term}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{term}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        /*
        |----------------------------------------------------------------------
        | MEDIA LIBRARY
        |----------------------------------------------------------------------
        */
        Route::prefix('media')->name('media.')->group(function () {
            // Library UI
            Route::get('/', [MediaController::class, 'index'])->name('index');

            // JSON listing/search/pagination for grid
            Route::get('/list', [MediaController::class, 'list'])->name('list');

            // Upload (FilePond)
            Route::post('/upload', [MediaController::class, 'upload'])->name('upload');

            // Update meta (title/name, alt, caption) and attach category
            Route::patch('/meta/{media}', [MediaController::class, 'updateMeta'])->name('meta');

            // Move (replace all categories in media_category taxonomy with one)
            Route::patch('/move/{media}', [MediaController::class, 'moveCategory'])->name('move');

            // Replace file (re-upload bytes for a record)
            Route::post('/replace/{media}', [MediaController::class, 'replaceFile'])->name('replace');

            // Soft delete to Trash
            Route::delete('/{media}', [MediaController::class, 'destroy'])->name('destroy');

            // Restore from Trash
            Route::post('/restore/{id}', [MediaController::class, 'restore'])->name('restore');

            // Permanent delete
            Route::delete('/force/{id}', [MediaController::class, 'forceDelete'])->name('force');

            // Media Categories (taxonomy = media_category)
            Route::prefix('categories')->name('categories.')->group(function () {
                // Blade screens
                Route::get('/', [MediaCategoryController::class, 'index'])->name('index');
                Route::get('/create', [MediaCategoryController::class, 'create'])->name('create');
                Route::post('/', [MediaCategoryController::class, 'store'])->name('store');
                Route::get('/{tt}/edit', [MediaCategoryController::class, 'edit'])->name('edit');
                Route::patch('/{tt}', [MediaCategoryController::class, 'update'])->name('update');
                Route::delete('/{tt}', [MediaCategoryController::class, 'destroy'])->name('destroy');

                // JSON for dropdowns / AJAX
                Route::get('/json', [MediaCategoryController::class, 'json'])->name('json');

                // Quick-create category (AJAX) — matches JS: /admin/media/categories/quick-create
                Route::post('/quick-create', [MediaCategoryController::class, 'quickCreate'])->name('quick-create');
            });
        });
    });

require __DIR__ . '/auth.php';