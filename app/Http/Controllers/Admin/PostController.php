<?php

namespace App\Http\Controllers\Admin;

use App\Models\Post;
use App\Models\TermTaxonomy;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Support\SyncFeatured;

class PostController extends BaseContentController
{
    /** Determine current content type from route name: 'post' or 'page' */
    protected function currentType(): string
    {
        return request()->routeIs('admin.pages.*') ? 'page' : 'post';
    }

    /** Used by BaseContentController (if it calls it) */
    protected function contentType(): string
    {
        return $this->currentType();
    }

    /* -----------------------------------------------------------------
     | Create / Edit Views
     * ----------------------------------------------------------------*/

    public function create()
    {
        $type = $this->currentType();

        $post = new Post([
            'type' => $type,
            'status' => 'draft',
            'visibility' => 'public',
            'is_sticky' => false,
            'allow_comments' => true,
        ]);

        return view($type === 'page' ? 'admin.pages.create' : 'admin.posts.create', [
            'post' => $post,
            'type' => $type,
            'categoriesTree' => $type === 'post' ? $this->categoriesTree() : [],
            'selectedCategoryIds' => [],
            'templates' => $this->templateOptions($type),
        ]);
    }

    public function edit(Post $post)
    {
        $type = $this->currentType();
        abort_unless($post->type === $type, 404);

        $post->load(['seo', 'metas', 'featuredMedia', 'gallery', 'revisions']);

        $selectedCategoryIds = [];
        if ($type === 'post') {
            $selectedCategoryIds = DB::table('term_relationships as tr')
                ->join('term_taxonomies as tt', 'tt.id', '=', 'tr.term_taxonomy_id')
                ->where('tr.object_id', $post->id)
                ->where('tt.taxonomy', 'category')
                ->pluck('tr.term_taxonomy_id')
                ->map(fn($v) => (int) $v)
                ->all();
        }

        return view($type === 'page' ? 'admin.pages.edit' : 'admin.posts.edit', [
            'post' => $post,
            'type' => $type,
            'categoriesTree' => $type === 'post' ? $this->categoriesTree() : [],
            'selectedCategoryIds' => $selectedCategoryIds,
            'templates' => $this->templateOptions($type),
        ]);
    }

    /* -----------------------------------------------------------------
     | Store & Update
     * ----------------------------------------------------------------*/

    public function store(Request $request)
    {
        $type = $this->currentType();
        $data = $this->validatedData($request);
        $galleryIds = $this->normalizedGalleryIds($request);

        $post = new Post();
        $post->fill($data);
        $post->type = $type;
        $post->author_id = $post->author_id ?: (Auth::id() ?? null);

        // Featured fallback
        if (empty($post->featured_media_id) && !empty($galleryIds)) {
            $post->featured_media_id = $galleryIds[0];
        }

        if (($post->status ?? null) === 'published' && empty($post->published_at)) {
            $post->published_at = now();
        }

        $post->save();

        // ✅ Sync SEO, Meta, Tags
        $this->syncSeo($post, (array) $request->input('seo', []));
        $this->syncMetas($post, (array) $request->input('meta', []));
        $this->syncTags($post, $request);

        SyncFeatured::run($post);

        if ($type === 'post') {
            $this->syncCategories($post, $this->extractCategoryIds($request));
        }
        $this->syncGallery($post, $galleryIds);
        $this->syncSpatieFeaturedFromLegacy($post);

        return redirect()
            ->route($this->indexRouteForType($type))
            ->with('success', ucfirst($type) . ' created.');
    }

    public function update(Request $request, Post $post)
    {
        $type = $this->currentType();
        abort_unless($post->type === $type, 404);

        $data = $this->validatedData($request, $post->id);
        $galleryIds = $this->normalizedGalleryIds($request);

        $post->fill($data);

        // Featured fallback
        if (empty($post->featured_media_id) && !empty($galleryIds)) {
            $post->featured_media_id = $galleryIds[0];
        }

        if (($post->status ?? null) === 'published' && empty($post->published_at)) {
            $post->published_at = now();
        }

        $post->save();

        // ✅ Sync SEO, Meta, Tags
        $this->syncSeo($post, (array) $request->input('seo', []));
        $this->syncMetas($post, (array) $request->input('meta', []));
        $this->syncTags($post, $request);

        SyncFeatured::run($post);

        if ($type === 'post') {
            $this->syncCategories($post, $this->extractCategoryIds($request));
        }
        $this->syncGallery($post, $galleryIds);
        $this->syncSpatieFeaturedFromLegacy($post);

        return redirect()
            ->route($this->indexRouteForType($type))
            ->with('success', ucfirst($type) . ' updated.');
    }

    /* -----------------------------------------------------------------
     | Validation & request helpers
     * ----------------------------------------------------------------*/

    protected function validatedData(Request $request, ?int $postId = null): array
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

            // unified picker gallery[]
            'gallery' => ['sometimes', 'array'],
            'gallery.*' => ['integer', 'exists:media,id'],

            // ✅ SEO and Meta arrays
            'seo' => ['sometimes', 'array'],
            'seo.meta_title' => ['nullable', 'string', 'max:255'],
            'seo.meta_keywords' => ['nullable', 'string', 'max:500'],
            'seo.meta_description' => ['nullable', 'string', 'max:500'],
            'seo.robots_index' => ['nullable', 'boolean'],
            'seo.robots_follow' => ['nullable', 'boolean'],

            'meta' => ['sometimes', 'array'],
            'meta.*.key' => ['nullable', 'string', 'max:255'],
            'meta.*.value' => ['nullable', 'string', 'max:1000'],
        ], [
            'featured_media_id.exists' => 'Selected featured image does not exist.',
        ]);
    }

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

    protected function extractCategoryIds(Request $request): array
    {
        $ids = array_filter(array_map('intval', (array) $request->input('categories', [])));
        $ids2 = array_filter(array_map('intval', (array) $request->input('category_ids', [])));
        $all = array_values(array_unique(array_merge($ids, $ids2)));

        if (empty($all))
            return [];

        return TermTaxonomy::query()
            ->whereIn('id', $all)
            ->where('taxonomy', 'category')
            ->pluck('id')
            ->map(fn($v) => (int) $v)
            ->all();
    }

    /* -----------------------------------------------------------------
     | SYNC HELPERS
     * ----------------------------------------------------------------*/

    protected function syncCategories(Post $post, array $termTaxonomyIds): void
    {
        $existing = DB::table('term_relationships')
            ->select('term_taxonomy_id')
            ->where('object_id', $post->id)
            ->pluck('term_taxonomy_id')
            ->map(fn($v) => (int) $v)
            ->all();

        $categoryTtIds = TermTaxonomy::query()
            ->whereIn('id', $existing)
            ->where('taxonomy', 'category')
            ->pluck('id')
            ->map(fn($v) => (int) $v)
            ->all();

        $toDelete = array_diff($categoryTtIds, $termTaxonomyIds);
        if (!empty($toDelete)) {
            DB::table('term_relationships')
                ->where('object_id', $post->id)
                ->whereIn('term_taxonomy_id', $toDelete)
                ->delete();
        }

        $toInsert = array_diff($termTaxonomyIds, $categoryTtIds);
        if (!empty($toInsert)) {
            $rows = array_map(fn($ttId) => [
                'object_id' => $post->id,
                'term_taxonomy_id' => $ttId,
            ], $toInsert);
            DB::table('term_relationships')->insert($rows);
        }
    }

    protected function syncGallery(Post $post, array $mediaIds): void
    {
        DB::table('post_media')
            ->where('post_id', $post->id)
            ->where('role', 'featured')
            ->delete();

        if (empty($mediaIds))
            return;

        $rows = [];
        $pos = 1;
        foreach ($mediaIds as $mid) {
            $rows[] = [
                'post_id' => $post->id,
                'media_id' => $mid,
                'role' => 'featured',
                'position' => $pos++,
            ];
        }
        DB::table('post_media')->insert($rows);
    }

    protected function syncSpatieFeaturedFromLegacy(Post $post): void
    {
        if (!$this->spatieMediaReady())
            return;

        if (method_exists($post, 'clearMediaCollection')) {
            $post->clearMediaCollection('images');
        }

        $legacy = $post->featuredMedia;
        if (!$legacy)
            return;

        $disk = $legacy->disk ?: 'public';
        $relPath = $legacy->path ?? $legacy->file_path ?? null;

        if ($relPath && method_exists($post, 'addMediaFromDisk')) {
            try {
                $post->addMediaFromDisk($relPath, $disk)
                    ->preservingOriginal()
                    ->withResponsiveImages()
                    ->toMediaCollection('images');
                return;
            } catch (\Throwable $e) {
            }
        }

        $abs = $this->resolveLegacyMediaAbsolutePath($legacy);
        if ($abs && is_file($abs) && method_exists($post, 'addMedia')) {
            try {
                $post->addMedia($abs)
                    ->preservingOriginal()
                    ->withResponsiveImages()
                    ->toMediaCollection('images');
            } catch (\Throwable $e) {
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

    /* -----------------------------------------------------------------
     | Categories UI & Route helpers
     * ----------------------------------------------------------------*/

    protected function categoriesTree(): array
    {
        $cols = Schema::getColumnListing('term_taxonomies');
        $parentCol = in_array('parent', $cols, true)
            ? 'parent'
            : (in_array('parent_id', $cols, true) ? 'parent_id' : null);

        $hasTermRelation = method_exists(TermTaxonomy::class, 'term');
        $rows = TermTaxonomy::query()
            ->where('taxonomy', 'category')
            ->when($hasTermRelation, fn($q) => $q->with('term'))
            ->get();

        if (!$parentCol) {
            return $rows->sortBy(fn($r) => $hasTermRelation ? (optional($r->term)->name ?? '') : '')
                ->map(function ($r) use ($hasTermRelation) {
                    return [
                        'id' => (int) $r->id,
                        'term_id' => (int) $r->term_id,
                        'name' => $hasTermRelation ? (optional($r->term)->name ?? ('Term #' . $r->term_id)) : ('Term #' . $r->term_id),
                        'slug' => $hasTermRelation ? optional($r->term)->slug : null,
                        'parent' => 0,
                        'depth' => 0,
                    ];
                })
                ->values()
                ->all();
        }

        $childrenByParent = [];
        foreach ($rows as $r) {
            $p = (int) ($r->{$parentCol} ?? 0);
            $childrenByParent[$p][] = $r;
        }

        $out = [];
        $visited = [];

        $walk = function (int $parentId, int $depth) use (&$walk, &$out, &$visited, $childrenByParent, $parentCol, $hasTermRelation) {
            foreach ($childrenByParent[$parentId] ?? [] as $r) {
                if (isset($visited[$r->id]))
                    continue;
                $visited[$r->id] = true;

                $out[] = [
                    'id' => (int) $r->id,
                    'term_id' => (int) $r->term_id,
                    'name' => $hasTermRelation ? (optional($r->term)->name ?? ('Term #' . $r->term_id)) : ('Term #' . $r->term_id),
                    'slug' => $hasTermRelation ? optional($r->term)->slug : null,
                    'parent' => (int) ($r->{$parentCol} ?? 0),
                    'depth' => $depth,
                ];

                $walk((int) $r->id, $depth + 1);
            }
        };

        $walk(0, 0);
        foreach ($rows as $r) {
            if (!isset($visited[$r->id])) {
                $walk((int) $r->id, 0);
            }
        }

        return $out;
    }

    protected function templateOptions(string $type): array
    {
        $cfg = config("theme.templates.$type");
        return is_array($cfg) && !empty($cfg) ? $cfg : [];
    }

    protected function indexRouteForType(string $type): string
    {
        return $type === 'page' ? 'admin.pages.index' : 'admin.posts.index';
    }

    public function destroy(Post $post)
    {
        abort_unless($post->type === 'post', 404);

        DB::table('term_relationships')->where('object_id', $post->id)->delete();
        DB::table('post_media')->where('post_id', $post->id)->delete();
        $post->delete();

        return redirect()->route('admin.posts.index')->with('success', 'Post deleted.');
    }

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
        if (isset($legacy->path) && is_string($legacy->path) && is_file($legacy->path)) {
            return $legacy->path;
        }
        return null;
    }
}