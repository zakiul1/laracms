<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Term;
use App\Models\TermRelationship;
use App\Models\TermTaxonomy;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


abstract class BaseContentController extends Controller
{
    /** 'post' or 'page' */
    abstract protected function contentType(): string;

    public function index(Request $r)
    {
        $q = Post::query()->type($this->contentType())->latest('id');
        $s = trim((string) $r->input('s', ''));
        if ($s !== '') {
            $q->where(fn($x) => $x->where('title', 'like', "%$s%")->orWhere('slug', 'like', "%$s%"));
        }

        return view('admin.posts.index', [
            'items' => $q->paginate(20),
            'type' => $this->contentType(),
            'screen' => $this->contentType() === 'post' ? 'Posts' : 'Pages',
        ]);
    }

    public function create()
    {
        return view('admin.posts.create', [
            'post' => new Post([
                'type' => $this->contentType(),
                'status' => 'draft',
                'visibility' => 'public',
            ]),
            'type' => $this->contentType(),
        ]);
    }

    public function store(Request $r)
    {
        $this->normalizeAction($r);

        $data = $this->validated($r);
        $data['type'] = $this->contentType();
        $data['author_id'] = $r->user()->id; // ⬅️ current user is the author
        $this->applyPublishTimestamp($data);

        return DB::transaction(function () use ($r, $data) {
            $post = Post::create($data);

            $this->syncTaxonomies($post, $r);
            $this->syncGallery($post, (array) $r->input('gallery', []));
            $this->syncMetas($post, (array) $r->input('meta', []));
            $this->syncSeo($post, (array) $r->input('seo', []));

            $this->snapshot($post, $r->user());

            return redirect()->route($this->routeBase() . '.index')
                ->with('success', ucfirst($this->contentType()) . ' created.');
        });
    }

    public function edit(Post $post)
    {
        abort_unless($post->type === $this->contentType(), 404);

        $post->load(['seo', 'metas', 'featuredMedia', 'gallery', 'revisions']);

        return view('admin.posts.edit', [
            'post' => $post,
            'type' => $this->contentType(),
        ]);
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

    /** Validation & normalization */
    protected function validated(Request $r, ?int $ignoreId = null): array
    {
        $type = $this->contentType();

        $v = $r->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('posts', 'slug')->ignore($ignoreId)
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

        // Slug default + ensure unique within type (including trashed)
        $v['slug'] = trim((string) ($v['slug'] ?? ''));
        if ($v['slug'] === '') {
            $v['slug'] = Str::slug($v['title']) ?: Str::random(8);
        }
        $v['slug'] = $this->uniqueSlugWithinType($type, $v['slug'], $ignoreId);

        // Remove legacy/unwanted fields if present
        unset($v['is_sticky'], $v['allow_comments'], $v['featured_media_id']);

        // Template only matters for pages; strip if this is a post
        if ($type === 'post') {
            unset($v['template']);
        }

        return $v;
    }

    protected function routeBase(): string
    {
        return $this->contentType() === 'post' ? 'admin.posts' : 'admin.pages';
    }

    /* ------------------ TAXONOMIES / METAS / SEO / GALLERY ------------------ */

    protected function syncTaxonomies(Post $post, Request $r): void
    {
        // Pages: no categories
        if ($this->contentType() !== 'post') {
            TermRelationship::where('object_id', $post->id)
                ->whereIn('term_taxonomy_id', function ($q) {
                    $q->select('id')->from('term_taxonomies')->where('taxonomy', 'category');
                })
                ->delete();
            return;
        }

        // Accept either term_taxonomy IDs or term IDs
        $incoming = collect($r->input('categories', []))->filter();

        $ttxIds = $incoming->map(function ($raw) {
            $id = (int) $raw;
            if ($id <= 0)
                return null;

            // already a term_taxonomy.id?
            if (TermTaxonomy::where('id', $id)->where('taxonomy', 'category')->exists()) {
                return $id;
            }

            // treat as term.id -> map to term_taxonomy.id
            $ttx = TermTaxonomy::where('term_id', $id)->where('taxonomy', 'category')->first();
            return $ttx?->id;
        })->filter()->unique()->values();

        // Remove old category relations that are not selected
        TermRelationship::where('object_id', $post->id)
            ->whereIn('term_taxonomy_id', function ($q) {
                $q->select('id')->from('term_taxonomies')->where('taxonomy', 'category');
            })
            ->when($ttxIds->isNotEmpty(), fn($q) => $q->whereNotIn('term_taxonomy_id', $ttxIds))
            ->delete();

        // Upsert current selections
        $hasTermOrder = Schema::hasColumn('term_relationships', 'term_order');

        foreach ($ttxIds as $ttxId) {
            $keys = ['object_id' => $post->id, 'term_taxonomy_id' => $ttxId];
            $values = $hasTermOrder ? ['term_order' => 0] : [];

            // if term_order column doesn't exist, this inserts/keeps the row without it
            DB::table('term_relationships')->updateOrInsert($keys, $values);
        }
    }


    protected function syncMetas(Post $post, array $rows): void
    {
        $keep = [];
        foreach ($rows as $row) {
            $key = trim((string) ($row['key'] ?? ''));
            if ($key === '')
                continue;

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
        // Remove any existing gallery/featured rows to avoid ghosts
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

    /* ------------------------------- helpers ------------------------------- */

    protected function normalizeAction(Request $r): void
    {
        $action = strtolower((string) $r->input('action', 'save'));
        $publishLike = in_array($action, ['publish', 'publish_now', 'publish-post', 'publish-page'], true);

        if ($publishLike) {
            $r->merge(['status' => 'published', 'published_at' => null]);
        } else {
            $r->merge(['status' => 'draft', 'published_at' => null]);
        }
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

    /** Ensure slug is unique within type, including SOFT-DELETED rows. */
    protected function uniqueSlugWithinType(string $type, string $slug, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug) ?: Str::random(8);
        $candidate = $base;
        $i = 2;

        // include trashed rows to avoid DB unique key collisions
        $baseQuery = Post::withoutGlobalScopes()->where('type', $type);

        while (
            (clone $baseQuery)
                ->where('slug', $candidate)
                ->when($ignoreId, fn($q) => $q->where('id', '<>', $ignoreId))
                ->exists()
        ) {
            $candidate = "{$base}-{$i}";
            $i++;
            if ($i > 200)
                break;
        }

        return $candidate;
    }
}