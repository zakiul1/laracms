<?php

namespace App\Http\Controllers\Admin;

use App\Models\Post;
use App\Models\Media; // legacy/custom Media model (your own)
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

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

    /** Mirror legacy featured to Spatie so conversions run */
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

        $path = $this->resolveLegacyMediaAbsolutePath($legacy);
        if (!$path || !is_file($path))
            return;

        $mime = @mime_content_type($path) ?: '';
        if (strpos($mime, 'image/') !== 0)
            return;

        if (method_exists($page, 'addMedia')) {
            try {
                $page->addMedia($path)
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

    /** Provide template dropdown options */
    protected function templateOptions(string $type): array
    {
        $cfg = config("theme.templates.$type");
        return is_array($cfg) && !empty($cfg) ? $cfg : [];
    }
    public function destroy(Post $page)
    {
        abort_unless($page->type === 'page', 404);

        DB::table('post_media')->where('post_id', $page->id)->delete(); // optional cleanup
        $page->delete(); // SoftDeletes on Post

        return redirect()->route('admin.pages.index')->with('success', 'Page deleted.');
    }

}