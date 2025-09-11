<?php

namespace App\Http\Controllers\Admin;

use App\Models\Post;
use App\Models\Media; // legacy/custom Media model (your own)
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Support\Appearance\TemplateScanner;
use App\Support\Appearance\ThemeManager;
use Illuminate\Support\Str;

class PageController extends BaseContentController
{
    protected function contentType(): string
    {
        return 'page';
    }

    /** Create page */
    public function create()
    {
        $page = new Post([
            'type' => 'page',
            'status' => 'draft',
            'visibility' => 'public',
            'is_sticky' => false,
            'allow_comments' => true,
        ]);

        return view('admin.pages.create', [
            'post' => $page,           // form expects `post`
            'type' => 'page',
            'categoriesTree' => [],    // pages don't use categories
            'templates' => $this->templateOptions('page'),
        ]);
    }

    /** Edit page */
    public function edit(Post $page)
    {
        abort_unless($page->type === 'page', 404);

        $page->load(['seo', 'metas', 'featuredMedia', 'gallery', 'revisions']);

        return view('admin.pages.edit', [
            'post' => $page,
            'type' => 'page',
            'categoriesTree' => [],    // pages don't use categories
            'templates' => $this->templateOptions('page'),
        ]);
    }

    /* -----------------------------------------------------------------
     | Store & Update (pages don't have categories)
     * ----------------------------------------------------------------*/

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $galleryIds = $this->normalizedGalleryIds($request);

        $page = new Post();
        $page->fill($data);
        $page->type = 'page';
        $page->author_id = $page->author_id ?: (Auth::id() ?? null);

        // Featured fallback from first gallery
        if (empty($page->featured_media_id) && !empty($galleryIds)) {
            $page->featured_media_id = $galleryIds[0];
        }

        if (($page->status ?? null) === 'published' && empty($page->published_at)) {
            $page->published_at = now();
        }

        $page->save();

        $this->syncGallery($page, $galleryIds);
        $this->syncSpatieFeaturedFromLegacy($page);

        return redirect()->route('admin.pages.index')->with('success', 'Page created.');
    }

    public function update(Request $request, Post $page)
    {
        abort_unless($page->type === 'page', 404);

        $data = $this->validatedData($request, $page->id);
        $galleryIds = $this->normalizedGalleryIds($request);

        $page->fill($data);

        // Featured fallback from first gallery
        if (empty($page->featured_media_id) && !empty($galleryIds)) {
            $page->featured_media_id = $galleryIds[0];
        }

        if (($page->status ?? null) === 'published' && empty($page->published_at)) {
            $page->published_at = now();
        }

        $page->save();

        $this->syncGallery($page, $galleryIds);
        $this->syncSpatieFeaturedFromLegacy($page);

        return redirect()->route('admin.pages.index')->with('success', 'Page updated.');
    }

    /* -----------------------------------------------------------------
     | Validation & helpers
     * ----------------------------------------------------------------*/

    protected function validatedData(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string'],
            'template' => ['nullable', 'string', 'max:255'],
            'format' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:draft,pending,published,private,scheduled'],
            'visibility' => ['required', 'in:public,private,password'],
            'password' => ['nullable', 'string', 'max:255'],
            'published_at' => ['nullable', 'date'],
            'is_sticky' => ['sometimes', 'boolean'],
            'allow_comments' => ['sometimes', 'boolean'],
            'allow_pingbacks' => ['sometimes', 'boolean'],
            'featured_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'author_id' => ['nullable', 'integer', 'exists:users,id'],

            // unified picker
            'gallery' => ['sometimes', 'array'],
            'gallery.*' => ['integer', 'exists:media,id'],
        ], [
            'featured_media_id.exists' => 'Selected featured image does not exist.',
        ]);
    }

    /** Keep order, ints, unique */
    protected function normalizedGalleryIds(Request $request): array
    {
        $ids = array_map('intval', (array) $request->input('gallery', []));
        $seen = [];
        $out = [];
        foreach ($ids as $id) {
            if ($id && !isset($seen[$id])) {
                $seen[$id] = true;
                $out[] = $id;
            }
        }
        return $out;
    }

    /** Persist gallery to post_media (role=featured, with position) */
    protected function syncGallery(Post $page, array $mediaIds): void
    {
        DB::table('post_media')
            ->where('post_id', $page->id)
            ->where('role', 'featured')
            ->delete();

        if (empty($mediaIds))
            return;

        $rows = [];
        $pos = 1;
        foreach ($mediaIds as $mid) {
            $rows[] = [
                'post_id' => $page->id,
                'media_id' => $mid,
                'role' => 'featured',
                'position' => $pos++,
            ];
        }
        DB::table('post_media')->insert($rows);
    }

    /**
     * Mirror legacy featured to Spatie so conversions run,
     * WITHOUT moving/deleting the original legacy file.
     */
    protected function syncSpatieFeaturedFromLegacy(Post $page): void
    {
        if (!$this->spatieMediaReady())
            return;

        if (method_exists($page, 'clearMediaCollection')) {
            $page->clearMediaCollection('images');
        }

        $legacy = $page->featuredMedia; // belongsTo(Media::class, 'featured_media_id')
        if (!$legacy)
            return;

        // Prefer non-destructive add from the legacy disk/path
        $disk = $legacy->disk ?: 'public';
        $relPath = $legacy->path ?? $legacy->file_path ?? null;

        if ($relPath && method_exists($page, 'addMediaFromDisk')) {
            try {
                $page->addMediaFromDisk($relPath, $disk)
                    ->preservingOriginal()                  // do NOT move/remove the legacy file
                    ->withResponsiveImages()
                    ->toMediaCollection('images');
                return;
            } catch (\Throwable $e) {
                // fall through to absolute path approach
            }
        }

        // Fallback: absolute path (still preserve original)
        $path = $this->resolveLegacyMediaAbsolutePath($legacy);
        if (!$path || !is_file($path))
            return;

        $mime = @mime_content_type($path) ?: '';
        if (strpos($mime, 'image/') !== 0)
            return;

        if (method_exists($page, 'addMedia')) {
            try {
                $page->addMedia($path)
                    ->preservingOriginal()                  // keep legacy file intact
                    ->withResponsiveImages()
                    ->toMediaCollection('images');
            } catch (\Throwable $e) {
                // swallow/log
            }
        }
    }

    protected function spatieMediaReady(): bool
    {
        try {
            $class = config('media-library.media_model');
            if (!$class || !class_exists($class))
                return false;

            $m = app($class);
            $table = $m->getTable();

            if (!Schema::hasTable($table))
                return false;

            return Schema::hasColumn($table, 'model_id') && Schema::hasColumn($table, 'model_type');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Resolve absolute path for your legacy Media model */
    protected function resolveLegacyMediaAbsolutePath(Media $legacy): ?string
    {
        if (method_exists($legacy, 'absolutePath'))
            return $legacy->absolutePath();
        if (method_exists($legacy, 'getAbsolutePath'))
            return $legacy->getAbsolutePath();

        if (isset($legacy->disk, $legacy->path)) {
            try {
                return Storage::disk($legacy->disk)->path($legacy->path);
            } catch (\Throwable $e) {
            }
        }
        if (isset($legacy->disk, $legacy->file_path)) {
            try {
                return Storage::disk($legacy->disk)->path($legacy->file_path);
            } catch (\Throwable $e) {
            }
        }
        if (isset($legacy->path) && is_string($legacy->path) && is_file($legacy->path))
            return $legacy->path;

        return null;
    }

    /** Provide template dropdown options (scanner-first, with robust fallback) */
    protected function templateOptions(string $type = 'page'): array
    {
        // 1) Prefer the dedicated scanner (handles your paths & headers)
        try {
            $opts = app(TemplateScanner::class)->list($type === 'post' ? 'post' : 'page');
            if (!empty($opts)) {
                return $opts;
            }
        } catch (\Throwable $e) {
            // ignore and fall back
        }

        // 2) Fallback: on-disk scan across common layouts
        $slug = '';
        try {
            $slug = (string) app(ThemeManager::class)->activeSlug();
        } catch (\Throwable $e) {
        }
        if ($slug === '') {
            $slug = (string) (config('appearance.active_theme') ?? env('APP_THEME', 'default'));
        }
        if ($slug === '') {
            return [];
        }

        // Candidate roots
        $roots = [];
        $r1 = resource_path("views/themes/{$slug}"); // /resources/views/themes/<slug>
        $r2 = resource_path("themes/{$slug}");       // /resources/themes/<slug> (alt layout)
        foreach ([$r1, $r2] as $root) {
            if (is_dir($root)) {
                $roots[] = $root;
                if (is_dir($root . '/views')) {
                    $roots[] = $root . '/views';     // some themes nest another /views
                }
            }
        }
        if (!$roots) {
            return [];
        }

        // Patterns (page type)
        $patterns = [
            '/templates/page/*.blade.php',
            '/pages/*.blade.php',
            '/pages/templates/*.blade.php',   // your current path
            '/page/templates/*.blade.php',
            '/templates/pages/*.blade.php',
            '/page-*.blade.php',
        ];

        $found = [];
        foreach ($roots as $root) {
            foreach ($patterns as $p) {
                foreach ((glob($root . $p) ?: []) as $path) {
                    if (is_file($path)) {
                        $found[$this->normalizePath($path)] = true;
                    }
                }
            }
        }

        $options = [];
        foreach (array_keys($found) as $path) {
            $file = basename($path, '.blade.php'); // ex: full-width
            if ($file === 'default' || str_starts_with($file, '_')) {
                continue; // skip partials
            }

            $label = Str::of($file)->replace(['-', '_'], ' ')->title()->value();
            $head = @file_get_contents($path, false, null, 0, 4096) ?: '';

            // Prefer header-provided label(s)
            if (preg_match('/\{\-\-\s*Template\s*:\s*(.+?)\s*\-\-\}/is', $head, $m)) {
                $label = trim($m[1]);
            } elseif (preg_match('/\{\-\-\s*Template\s*Name\s*:\s*(.+?)\s*\-\-\}/is', $head, $m2)) {
                $label = trim($m2[1]);
            }

            // Respect scoping: For|Types|PostType: page|post
            $scoped = $this->parseTypesFromHeader($head);
            if ($scoped && !in_array('page', $scoped, true)) {
                continue;
            }

            // Use file slug as the saved value (matches your form & renderer)
            $options[$file] = $label;
        }

        ksort($options, SORT_NATURAL | SORT_FLAG_CASE);
        return $options;
    }

    // ---- small helpers for fallback ----

    protected function normalizePath(string $p): string
    {
        return str_replace('\\', '/', $p);
    }

    protected function parseTypesFromHeader(string $head): array
    {
        $keys = ['For', 'Types', 'PostType'];
        $out = [];
        foreach ($keys as $k) {
            if (preg_match('/\{\-\-\s*' . $k . '\s*:\s*(.+?)\s*\-\-\}/is', $head, $m)) {
                $vals = preg_split('/[,|]/', $m[1]) ?: [];
                foreach ($vals as $v) {
                    $v = Str::lower(trim($v));
                    if ($v !== '') {
                        $out[$v] = true;
                    }
                }
            }
        }
        return array_keys($out);
    }

    public function destroy(Post $page)
    {
        abort_unless($page->type === 'page', 404);

        DB::table('post_media')->where('post_id', $page->id)->delete(); // optional cleanup
        $page->delete(); // SoftDeletes on Post

        return redirect()->route('admin.pages.index')->with('success', 'Page deleted.');
    }
}