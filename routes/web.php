<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\EditorUploadController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MediaCategoryController;
// Plugin Manager
use App\Http\Controllers\Admin\PluginController;
// Menus module
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\Admin\MenuLocationController;
// Runtime taxonomy + tags (for editor)
use App\Http\Controllers\Admin\TaxonomyQuickController;
use App\Http\Controllers\Admin\TagController;

/**
 * Appearance (core, like WordPress)
 */
use App\Http\Controllers\Admin\Appearance\ThemeController;
use App\Http\Controllers\Admin\Appearance\CustomizerController;
use App\Http\Controllers\Admin\Appearance\BackgroundController;
use App\Http\Controllers\Admin\Appearance\HeaderController;
use App\Http\Controllers\Admin\Appearance\WidgetsController;
use App\Http\Controllers\Admin\Appearance\ThemeEditorController;

/**
 * Settings
 */
use App\Http\Controllers\Admin\Settings\SettingsController;

// --------------------------------------------------
// Public home
// --------------------------------------------------
Route::get('/', fn() => view('welcome'))->name('home');

// --------------------------------------------------
// Authenticated user profile
// --------------------------------------------------
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// --------------------------------------------------
// Admin
// --------------------------------------------------
Route::middleware(['auth', 'admin'])->prefix('admin')->as('admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // CKEditor inline uploads
    Route::post('ckeditor/upload', [EditorUploadController::class, 'upload'])->name('ckeditor.upload');

    // -------------------------
    // Posts
    // -------------------------
    Route::resource('posts', PostController::class)->except(['show']);
    Route::get('/posts/{post}/revisions', [PostController::class, 'revisions'])
        ->whereNumber('post')->name('posts.revisions');
    Route::post('/posts/{post}/revisions/{revision}/restore', [PostController::class, 'restoreRevision'])
        ->whereNumber('post')->whereNumber('revision')->name('posts.revisions.restore');

    // -------------------------
    // Pages
    // -------------------------
    Route::resource('pages', PageController::class)->except(['show'])->parameters(['pages' => 'post']);
    Route::get('/pages/{post}/revisions', [PageController::class, 'revisions'])
        ->whereNumber('post')->name('pages.revisions');
    Route::post('/pages/{post}/revisions/{revision}/restore', [PageController::class, 'restoreRevision'])
        ->whereNumber('post')->whereNumber('revision')->name('pages.revisions.restore');

    // -------------------------
    // Categories
    // -------------------------
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('categories/{term}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::patch('categories/{term}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{term}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Runtime taxonomy helpers for editor (category quick-create)
    Route::post('/taxonomies/category/quick', [TaxonomyQuickController::class, 'quickCategory'])
        ->name('taxonomies.category.quick');

    // Tag suggest (autocomplete)
    Route::get('/tags/suggest', [TagController::class, 'suggest'])->name('tags.suggest');

    // -------------------------
    // Media Library
    // -------------------------
    Route::prefix('media')->name('media.')->group(function () {
        Route::get('/', [MediaController::class, 'index'])->name('index');
        Route::get('/list', [MediaController::class, 'list'])->name('list');
        Route::post('/upload', [MediaController::class, 'upload'])->name('upload');
        Route::patch('/meta/{media}', [MediaController::class, 'updateMeta'])->name('meta');
        Route::patch('/move/{media}', [MediaController::class, 'moveCategory'])->name('move');
        Route::post('/replace/{media}', [MediaController::class, 'replaceFile'])->name('replace');
        Route::get('/show/{media}', [MediaController::class, 'show'])->name('show');
        Route::delete('/{media}', [MediaController::class, 'destroy'])->name('destroy');
        Route::delete('/delete/{media}', [MediaController::class, 'destroy'])->name('delete'); // alias
        Route::post('/restore/{id}', [MediaController::class, 'restore'])->name('restore');
        Route::delete('/force/{id}', [MediaController::class, 'forceDelete'])->name('force');

        // ✅ Bulk actions (the missing ones)
        Route::post('/bulk-delete', [MediaController::class, 'bulkDelete'])->name('bulk-delete');
        Route::post('/bulk-restore', [MediaController::class, 'bulkRestore'])->name('bulk-restore');
        Route::post('/bulk-force-delete', [MediaController::class, 'bulkForceDelete'])->name('bulk-force');

        // Media categories
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

    // -------------------------
    // Menus (FULL MODULE)
    // -------------------------
    Route::prefix('menus')->name('menus.')->group(function () {
        // Menus CRUD
        Route::get('/', [MenuController::class, 'index'])->name('index');
        Route::post('/', [MenuController::class, 'store'])->name('store');
        Route::get('/{menu}/edit', [MenuController::class, 'edit'])->whereNumber('menu')->name('edit');
        Route::patch('/{menu}', [MenuController::class, 'update'])->whereNumber('menu')->name('update');
        Route::delete('/{menu}', [MenuController::class, 'destroy'])->whereNumber('menu')->name('destroy');

        // Menu Items (tree CRUD + reorder)
        Route::get('/{menu}/items', [MenuItemController::class, 'index'])->whereNumber('menu')->name('items.index');
        Route::post('/{menu}/items', [MenuItemController::class, 'store'])->whereNumber('menu')->name('items.store');
        Route::patch('/{menu}/items/{item}', [MenuItemController::class, 'update'])
            ->whereNumber('menu')->whereNumber('item')->name('items.update');
        Route::delete('/{menu}/items/{item}', [MenuItemController::class, 'destroy'])
            ->whereNumber('menu')->whereNumber('item')->name('items.destroy');

        // Reorder nested items
        Route::post('/{menu}/items/reorder', [MenuItemController::class, 'reorder'])
            ->whereNumber('menu')->name('items.reorder');

        // Optional utilities
        Route::post('/{menu}/items/{item}/toggle', [MenuItemController::class, 'toggle'])
            ->whereNumber('menu')->whereNumber('item')->name('items.toggle');
        Route::post('/{menu}/items/{item}/clone', [MenuItemController::class, 'clone'])
            ->whereNumber('menu')->whereNumber('item')->name('items.clone');

        // Sources for item pickers
        Route::get('/sources/pages', [MenuItemController::class, 'sourcePages'])->name('sources.pages');
        Route::get('/sources/posts', [MenuItemController::class, 'sourcePosts'])->name('sources.posts');
        Route::get('/sources/terms/{taxonomy?}', [MenuItemController::class, 'sourceTerms'])->name('sources.terms');

        // Locations
        Route::get('/locations', [MenuLocationController::class, 'index'])->name('locations');
        Route::post('/locations', [MenuLocationController::class, 'save'])->name('locations.save');
    });

    // -------------------------
    // Appearance
    // -------------------------
    Route::prefix('appearance')->name('appearance.')->group(function () {
        // Themes
        Route::get('/themes', [ThemeController::class, 'index'])->name('themes.index');
        Route::post('/themes/upload', [ThemeController::class, 'upload'])->name('themes.upload');
        Route::post('/themes/{slug}/activate', [ThemeController::class, 'activate'])
            ->where('slug', '[A-Za-z0-9\-_]+')->name('themes.activate');
        Route::post('/themes/{slug}/deactivate', [ThemeController::class, 'deactivate'])
            ->where('slug', '[A-Za-z0-9\-_]+')->name('themes.deactivate');
        Route::get('/themes/{slug}/preview', [ThemeController::class, 'preview'])
            ->where('slug', '[A-Za-z0-9\-_]+')->name('themes.preview');
        Route::delete('/themes/{slug}', [ThemeController::class, 'destroy'])
            ->where('slug', '[A-Za-z0-9\-_]+')->name('themes.destroy');
        Route::match(['POST', 'DELETE'], '/themes/{slug}/delete', [ThemeController::class, 'destroy'])
            ->where('slug', '[A-Za-z0-9\-_]+')->name('themes.delete');

        // Widgets
        Route::prefix('widgets')->name('widgets.')->group(function () {
            Route::get('/', [WidgetsController::class, 'index'])->name('index');

            // Areas
            Route::post('/areas', [WidgetsController::class, 'storeArea'])->name('areas.store');
            Route::patch('/areas/{area}', [WidgetsController::class, 'updateArea'])
                ->whereNumber('area')->name('areas.update');
            Route::delete('/areas/{area}', [WidgetsController::class, 'destroyArea'])
                ->whereNumber('area')->name('areas.delete');

            // Paginated listing for “Load more”
            Route::get('/areas/{area}/list', [WidgetsController::class, 'list'])
                ->whereNumber('area')->name('areas.list');

            // Widgets
            Route::post('/', [WidgetsController::class, 'store'])->name('store');
            Route::patch('/{widget}', [WidgetsController::class, 'update'])
                ->whereNumber('widget')->name('update');
            Route::post('/{widget}/toggle', [WidgetsController::class, 'toggle'])
                ->whereNumber('widget')->name('toggle');
            Route::post('/{widget}/clone', [WidgetsController::class, 'clone'])
                ->whereNumber('widget')->name('clone');
            Route::delete('/{widget}', [WidgetsController::class, 'destroy'])
                ->whereNumber('widget')->name('delete');

            // Ordering
            Route::post('/reorder', [WidgetsController::class, 'reorder'])->name('reorder');

            // Live preview
            Route::post('/preview', [WidgetsController::class, 'preview'])->name('preview');

            // Export / Import
            Route::get('/export', [WidgetsController::class, 'export'])->name('export');
            Route::post('/import', [WidgetsController::class, 'import'])->name('import');
        });

        // Customizer
        Route::get('/customize', [CustomizerController::class, 'index'])->name('customize');
        Route::post('/customize', [CustomizerController::class, 'save'])->name('customize.save');
        Route::get('/customize/preview', [CustomizerController::class, 'preview'])->name('customize.preview');

        // Background & Header
        Route::get('/background', [BackgroundController::class, 'index'])->name('background');
        Route::post('/background', [BackgroundController::class, 'save'])->name('background.save');

        Route::get('/header', [HeaderController::class, 'index'])->name('header');
        Route::post('/header', [HeaderController::class, 'save'])->name('header.save');

        // Theme Editor
        Route::get('/editor', [ThemeEditorController::class, 'index'])->name('editor.index');
        Route::get('/editor/tree', [ThemeEditorController::class, 'tree'])->name('editor.tree');
        Route::get('/editor/open', [ThemeEditorController::class, 'open'])->name('editor.open');
        Route::post('/editor/save', [ThemeEditorController::class, 'save'])->name('editor.save');
        Route::post('/editor/validate', [ThemeEditorController::class, 'validateSyntax'])->name('editor.validate');
    });

    // -------------------------
    // Settings (tabs)
    // -------------------------
    Route::prefix('settings')->name('settings.')->group(function () {
        // Hub
        Route::get('/', [SettingsController::class, 'index'])->name('index');

        // Explicit core tabs
        $pages = ['general', 'writing', 'reading', 'discussion', 'media', 'permalinks', 'privacy'];
        foreach ($pages as $p) {
            Route::get("/{$p}", [SettingsController::class, 'show'])
                ->defaults('page', $p)->name($p);
            Route::post("/{$p}/save", [SettingsController::class, 'save'])
                ->defaults('page', $p)->name("{$p}.save");
            Route::post("/{$p}/validate", [SettingsController::class, 'validatePage'])
                ->defaults('page', $p)->name("{$p}.validate");
            Route::post("/{$p}/defaults", [SettingsController::class, 'restoreDefaults'])
                ->defaults('page', $p)->name("{$p}.defaults");
        }

        // Dynamic fallback for plugin-added pages
        Route::get('/{page}', [SettingsController::class, 'show'])
            ->where('page', '[A-Za-z0-9\-_]+')->name('show');
        Route::post('/{page}/save', [SettingsController::class, 'save'])
            ->where('page', '[A-Za-z0-9\-_]+')->name('save');
        Route::post('/{page}/validate', [SettingsController::class, 'validatePage'])
            ->where('page', '[A-Za-z0-9\-_]+')->name('validate');
        Route::post('/{page}/defaults', [SettingsController::class, 'restoreDefaults'])
            ->where('page', '[A-Za-z0-9\-_]+')->name('defaults');

        // Import/Export (global)
        Route::get('/export/json', [SettingsController::class, 'export'])->name('export');
        Route::post('/import/json', [SettingsController::class, 'import'])->name('import');
    });

    // -------------------------
    // Plugin Manager
    // -------------------------
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
});

require __DIR__ . '/auth.php';