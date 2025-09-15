<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\TermRelationship;
use App\Models\TermTaxonomy;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Support\Appearance\ThemeManager;
use App\Models\Media as LegacyMedia;

abstract class BaseContentController extends Controller
{
    /** Must return 'post' or 'page' */
    abstract protected function contentType(): string;

    /** simple in-request cache to avoid re-scanning the filesystem repeatedly */
    protected static array $templateCache = [];

    public function index(Request $r)
    {
        $q = Post::query()->type($this->contentType())->latest('id');

        if ($s = trim((string) $r->input('s', ''))) {
            $q->where(fn($x) => $x
                ->where('title', 'like', "%{$s}%")
                ->orWhere('slug', 'like', "%{$s}%"));
        }

        return view('admin.posts.index', [
            'items' => $q->paginate(20),
            'type' => $this->contentType(),
            'screen' => $this->contentType() === 'post' ? 'Posts' : 'Pages',
        ]);
    }

    public function create()
    {
        $payload = [
            'post' => new Post([
                'type' => $this->contentType(),
                'status' => 'draft',
                'visibility' => 'public',
            ]),
            'type' => $this->contentType(),
        ];

        if ($this->contentType() === 'page') {
            // Pages: provide template list
            $payload['templates'] = $this->templateOptions('page');
            $payload['categoriesTree'] = [];
        } else {
            // Posts: categories (no templates for posts by default)
            $payload['categoriesTree'] = $this->categoriesTree();
            $payload['selectedCategoryIds'] = [];
        }

        return view('admin.posts.create', $payload);
    }

    public function store(Request $r)
    {
        $this->normalizeAction($r);

        $data = $this->validated($r);
        $data['type'] = $this->contentType();
        $data['author_id'] = $r->user()->id;
        $this->applyPublishTimestamp($data);

        return DB::transaction(function () use ($r, $data) {
            $post = Post::create($data);

            $this->syncTaxonomies($post, $r);
            $this->syncGallery($post, (array) $r->input('gallery', []));
            $this->syncMetas($post, (array) $r->input('meta', []));
            $this->syncSeo($post, (array) $r->input('seo', []));

            // 🔧 NEW: attach Spatie featured media
            $this->syncFeaturedMedia($r, $post);

            $this->snapshot($post, $r->user());

            return redirect()->route($this->routeBase() . '.index')
                ->with('success', ucfirst($this->contentType()) . ' created.');
        });
    }

    public function edit(Post $post)
    {
        abort_unless($post->type === $this->contentType(), 404);

        $post->load(['seo', 'metas', 'featuredMedia', 'gallery', 'revisions']);

        $payload = [
            'post' => $post,
            'type' => $this->contentType(),
        ];

        if ($this->contentType() === 'page') {
            $payload['templates'] = $this->templateOptions('page');
            $payload['categoriesTree'] = [];
        } else {
            $payload['categoriesTree'] = $this->categoriesTree();
            $payload['selectedCategoryIds'] = $this->selectedCategoryIds($post->id);
        }

        return view('admin.posts.edit', $payload);
    }

    public function update(Request $r, Post $post)
    {
        abort_unless($post->type === $this->contentType(), 404);

        $this->normalizeAction($r);
        $data = $this->validated($r, $post->id);
        $this->applyPublishTimestamp($data);

        return DB::transaction(function () use ($r, $post, $data) {
            $post->update($data);

            $this->syncTaxonomies($post, $r);
            $this->syncGallery($post, (array) $r->input('gallery', []));
            $this->syncMetas($post, (array) $r->input('meta', []));
            $this->syncSeo($post, (array) $r->input('seo', []));

            // 🔧 NEW: attach/update Spatie featured media
            $this->syncFeaturedMedia($r, $post);

            $this->snapshot($post, $r->user());

            return redirect()->route($this->routeBase() . '.index')
                ->with('success', ucfirst($this->contentType()) . ' updated.');
        });
    }

    public function destroy(Post $post)
    {
        abort_unless($post->type === $this->contentType(), 404);

        $post->delete();

        return redirect()->route($this->routeBase() . '.index')
            ->with('success', ucfirst($this->contentType()) . ' deleted.');
    }

    /* ---------------------- Validation & normalization ---------------------- */

    protected function validated(Request $r, ?int $ignoreId = null): array
    {
        $type = $this->contentType();

        $v = $r->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('posts', 'slug')
                    ->ignore($ignoreId)
                    ->where(fn($q) => $q->where('type', $type)),
            ],
            'content' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string'],
            'template' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'visibility' => ['required', Rule::in(['public', 'private', 'password'])],
            'password' => ['nullable', 'string', 'max:255'],
            'author_id' => ['nullable', 'exists:users,id'],
            'published_at' => ['nullable', 'date'],
        ]);

        // slug default + unique within type (incl. trashed)
        $v['slug'] = trim((string) ($v['slug'] ?? ''));
        if ($v['slug'] === '') {
            $v['slug'] = Str::slug($v['title']) ?: Str::random(8);
        }
        $v['slug'] = $this->uniqueSlugWithinType($type, $v['slug'], $ignoreId);

        // strip legacy/unwanted fields
        unset($v['is_sticky'], $v['allow_comments'], $v['featured_media_id']);

        // template only applies to pages
        if ($type === 'post') {
            unset($v['template']);
        }

        return $v;
    }

    protected function routeBase(): string
    {
        return $this->contentType() === 'post' ? 'admin.posts' : 'admin.pages';
    }

    /* ---------------- TAXONOMIES / METAS / SEO / GALLERY ---------------- */

    protected function syncTaxonomies(Post $post, Request $r): void
    {
        if ($this->contentType() !== 'post') {
            // pages: clear any category links
            TermRelationship::where('object_id', $post->id)
                ->whereIn('term_taxonomy_id', function ($q) {
                    $q->select('id')->from('term_taxonomies')->where('taxonomy', 'category');
                })
                ->delete();
            return;
        }

        // Accept either term_taxonomy ids or term ids
        $incoming = collect($r->input('categories', []))->filter();

        $ttxIds = $incoming->map(function ($raw) {
            $id = (int) $raw;
            if ($id <= 0) {
                return null;
            }

            if (TermTaxonomy::where('id', $id)->where('taxonomy', 'category')->exists()) {
                return $id;
            }
            $ttx = TermTaxonomy::where('term_id', $id)->where('taxonomy', 'category')->first();
            return $ttx?->id;
        })->filter()->unique()->values();

        // remove deselected
        TermRelationship::where('object_id', $post->id)
            ->whereIn('term_taxonomy_id', function ($q) {
                $q->select('id')->from('term_taxonomies')->where('taxonomy', 'category');
            })
            ->when($ttxIds->isNotEmpty(), fn($q) => $q->whereNotIn('term_taxonomy_id', $ttxIds))
            ->delete();

        // upsert selected
        $hasTermOrder = Schema::hasColumn('term_relationships', 'term_order');
        foreach ($ttxIds as $ttxId) {
            $keys = ['object_id' => $post->id, 'term_taxonomy_id' => $ttxId];
            $values = $hasTermOrder ? ['term_order' => 0] : [];
            DB::table('term_relationships')->updateOrInsert($keys, $values);
        }
    }

    protected function syncMetas(Post $post, array $rows): void
    {
        $keep = [];
        foreach ($rows as $row) {
            $key = trim((string) ($row['key'] ?? ''));
            if ($key === '') {
                continue;
            }

            $val = (string) ($row['value'] ?? '');
            $meta = $post->metas()->updateOrCreate(['meta_key' => $key], ['meta_value' => $val]);
            $keep[] = $meta->id;
        }

        if (!empty($keep)) {
            $post->metas()->whereNotIn('id', $keep)->delete();
        } else {
            $post->metas()->delete();
        }
    }

    protected function syncSeo(Post $post, array $seo): void
    {
        $payload = Arr::only($seo, [
            'meta_title',
            'meta_description',
            'meta_keywords',
            'robots_index',
            'robots_follow',
            'og_title',
            'og_description',
            'og_image_id',
            'twitter_title',
            'twitter_description',
            'twitter_image_id',
        ]);

        $payload['robots_index'] = (bool) ($payload['robots_index'] ?? true);
        $payload['robots_follow'] = (bool) ($payload['robots_follow'] ?? true);

        $post->seo()->updateOrCreate([], $payload);
    }

    /** Keep ONLY multiple featured images (gallery). */
    protected function syncGallery(Post $post, array $gallery): void
    {
        DB::table('post_media')
            ->where('post_id', $post->id)
            ->whereIn('role', ['featured', 'gallery'])
            ->delete();

        $position = 0;
        foreach ($gallery as $mediaId) {
            $mediaId = (int) $mediaId;
            if ($mediaId <= 0) {
                continue;
            }

            DB::table('post_media')->insert([
                'post_id' => $post->id,
                'media_id' => $mediaId,
                'role' => 'featured',
                'position' => $position++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * 🔧 NEW: Sync a Spatie "featured" media item for this post.
     * Priority:
     *   1) uploaded file:    input name 'featured_upload'
     *   2) legacy picker id: input name 'featured_media_id' (points to App\Models\Media)
     *   3) fall back to first gallery item (copy to 'featured')
     */
    protected function syncFeaturedMedia(Request $r, Post $post): void
    {
        // (1) Direct upload
        if ($r->hasFile('featured_upload')) {
            $post->clearMediaCollection('featured');
            $post->addMediaFromRequest('featured_upload')
                ->usingName($r->input('title') ?: 'Featured')
                ->withResponsiveImages()
                ->toMediaCollection('featured');

        }

        // (2) Legacy picker id OR DB column
        $pickId = $r->input('featured_media_id') ?: $post->featured_media_id;
        if ($pickId) {
            $legacy = \App\Models\Media::find((int) $pickId);
            if ($legacy) {
                $rp = \App\Support\Media\LegacyMediaHelper::resolveDiskAndPath($legacy);
                if ($rp) {
                    $post->clearMediaCollection('featured');

                    if (isset($rp['url'])) {
                        // if your legacy stores absolute URLs (e.g., s3 presigned), download first:
                        $tmp = tempnam(sys_get_temp_dir(), 'feat_');
                        file_put_contents($tmp, file_get_contents($rp['url']));
                        $post->addMedia($tmp)
                            ->usingName($legacy->title ?? 'Featured')
                            ->withCustomProperties(['alt' => $legacy->alt ?? $legacy->title ?? $post->title])
                            ->toMediaCollection('featured');
                        @unlink($tmp);
                        return;
                    }

                    if (!empty($rp['public'])) {
                        $full = public_path($rp['path']);
                        if (is_file($full)) {
                            $post->addMedia($full)
                                ->usingName($legacy->title ?? 'Featured')
                                ->withCustomProperties(['alt' => $legacy->alt ?? $legacy->title ?? $post->title])
                                ->toMediaCollection('featured');
                            return;
                        }
                    }

                    // disk + path
                    if (!empty($rp['disk']) && !empty($rp['path'])) {
                        $post->addMediaFromDisk($rp['path'], $rp['disk'])
                            ->preservingOriginal()
                            ->usingName($legacy->title ?? 'Featured')
                            ->withCustomProperties(['alt' => $legacy->alt ?? $legacy->title ?? $post->title])
                            ->toMediaCollection('featured');
                        return;
                    }
                }
            }
        }

        // (3) Fallback: gallery pivot
        $firstGalleryId = \DB::table('post_media')
            ->where('post_id', $post->id)
            ->whereIn('role', ['featured', 'gallery'])
            ->orderBy('position')
            ->value('media_id');

        if ($firstGalleryId) {
            $legacy = \App\Models\Media::find((int) $firstGalleryId);
            if ($legacy) {
                $rp = \App\Support\Media\LegacyMediaHelper::resolveDiskAndPath($legacy);
                if ($rp) {
                    $post->clearMediaCollection('featured');

                    if (isset($rp['url'])) {
                        $tmp = tempnam(sys_get_temp_dir(), 'feat_');
                        file_put_contents($tmp, file_get_contents($rp['url']));
                        $post->addMedia($tmp)
                            ->usingName($legacy->title ?? 'Featured')
                            ->withCustomProperties(['alt' => $legacy->alt ?? $legacy->title ?? $post->title])
                            ->toMediaCollection('featured');
                        @unlink($tmp);
                        return;
                    }

                    if (!empty($rp['public'])) {
                        $full = public_path($rp['path']);
                        if (is_file($full)) {
                            $post->addMedia($full)
                                ->usingName($legacy->title ?? 'Featured')
                                ->withCustomProperties(['alt' => $legacy->alt ?? $legacy->title ?? $post->title])
                                ->toMediaCollection('featured');
                            return;
                        }
                    }

                    if (!empty($rp['disk']) && !empty($rp['path'])) {
                        $post->addMediaFromDisk($rp['path'], $rp['disk'])
                            ->preservingOriginal()
                            ->usingName($legacy->title ?? 'Featured')
                            ->withCustomProperties(['alt' => $legacy->alt ?? $legacy->title ?? $post->title])
                            ->toMediaCollection('featured');
                        return;
                    }
                }
            }
        }

        // (4) Last fallback: copy from Spatie 'images'
        if (!$post->getFirstMedia('featured')) {
            if ($first = $post->getFirstMedia('images')) {
                $post->clearMediaCollection('featured');
                $first->copy($post, 'featured');
            }
        }
    }


    protected function snapshot(Post $post, $user): void
    {
        $post->revisions()->create([
            'author_id' => $user?->id,
            'title' => $post->title,
            'content' => $post->content,
            'excerpt' => $post->excerpt,
            'snapshot' => [
                'status' => $post->status,
                'visibility' => $post->visibility,
                'published_at' => optional($post->published_at)?->toAtomString(),
                'template' => $post->template,
                'featured_media_id' => $post->featured_media_id,
            ],
        ]);
    }

    /* -------------------------------- helpers ------------------------------- */

    protected function normalizeAction(Request $r): void
    {
        $action = strtolower((string) $r->input('action', 'save'));
        $publish = in_array($action, ['publish', 'publish_now', 'publish-post', 'publish-page'], true);

        $r->merge([
            'status' => $publish ? 'published' : 'draft',
            'published_at' => null,
        ]);
    }

    protected function applyPublishTimestamp(array &$data): void
    {
        if (($data['status'] ?? 'draft') === 'published') {
            if (empty($data['published_at'])) {
                $data['published_at'] = now();
            }
        } else {
            $data['published_at'] = null;
        }
    }

    /** Ensure slug is unique within type, including soft-deleted rows. */
    protected function uniqueSlugWithinType(string $type, string $slug, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug) ?: Str::random(8);
        $candidate = $base;
        $i = 2;

        $baseQuery = Post::withoutGlobalScopes()->where('type', $type);
        while (
            (clone $baseQuery)
                ->where('slug', $candidate)
                ->when($ignoreId, fn($q) => $q->where('id', '<>', $ignoreId))
                ->exists()
        ) {
            $candidate = "{$base}-{$i}";
            if (++$i > 200) {
                break;
            }
        }

        return $candidate;
    }

    /**
     * Discover templates from the active theme.
     *  - Pages: resources/themes/<active>/views/pages/templates/*.blade.php
     *  - Posts: resources/themes/<active>/views/posts/templates/*.blade.php
     *
     * Returns ['full-width' => 'Full Width', ...] (no empty "Default" here).
     */
    protected function templateOptions(string $type): array
    {
        try {
            // returns [] if none; your Blade shows "Default" itself
            return app(\App\Support\Appearance\TemplateScanner::class)->list($type);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /* ----------------------- helpers for post categories ----------------------- */

    protected function categoriesTree(): array
    {
        $rows = TermTaxonomy::with('term')
            ->where('taxonomy', 'category')
            ->get();

        $childrenByParent = [];
        foreach ($rows as $r) {
            $childrenByParent[(int) ($r->parent ?? 0)][] = $r;
        }

        $out = [];
        $visited = [];

        $walk = function (int $parentId, int $depth) use (&$walk, &$out, &$visited, $childrenByParent) {
            foreach (($childrenByParent[$parentId] ?? []) as $r) {
                if (isset($visited[$r->id])) {
                    continue;
                }
                $visited[$r->id] = true;

                $out[] = [
                    'id' => (int) $r->id,
                    'term_id' => (int) $r->term_id,
                    'name' => optional($r->term)->name ?? ('Term #' . $r->term_id),
                    'slug' => optional($r->term)->slug,
                    'parent' => (int) ($r->parent ?? 0),
                    'depth' => $depth,
                ];

                $walk((int) $r->id, $depth + 1);
            }
        };

        // start from root=0
        $walk(0, 0);

        // include any orphaned nodes
        foreach ($rows as $r) {
            if (!isset($visited[$r->id])) {
                $walk((int) $r->id, 0);
            }
        }

        return $out;
    }

    protected function selectedCategoryIds(int $postId): array
    {
        return DB::table('term_relationships as tr')
            ->join('term_taxonomies as tt', 'tt.id', '=', 'tr.term_taxonomy_id')
            ->where('tr.object_id', $postId)
            ->where('tt.taxonomy', 'category')
            ->pluck('tr.term_taxonomy_id')
            ->map(fn($v) => (int) $v)
            ->all();
    }
}