<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostMeta;
use App\Models\PostRevision;
use App\Models\Taxonomy;
use App\Models\Term;
use App\Models\TermTaxonomy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class PostController extends Controller
{
    /** GET /admin/posts/{type?} */
    public function index(Request $request, string $type = 'post')
    {
        $items = Post::type($type)->latest()->paginate(15);

        return view('admin.posts.index', [
            'items' => $items,
            'type' => $type,
        ]);
    }

    /** GET /admin/posts/create/{type?} */
    public function create(string $type = 'post')
    {
        // Taxonomy models (if your view shows labels, etc.)
        $categoryTax = Taxonomy::where('slug', 'category')->first();
        $tagTax = Taxonomy::where('slug', 'post_tag')->first();

        // Actual selectable terms (via term_taxonomies)
        $categories = Term::forTaxonomy('category')->orderBy('name')->get();
        $tags = Term::forTaxonomy('post_tag')->orderBy('name')->get();

        return view('admin.posts.create', [
            'type' => $type,
            'post' => new Post(['type' => $type]),
            'categoryTax' => $categoryTax,
            'tagTax' => $tagTax,
            'categories' => $categories,
            'tags' => $tags,
            'mode' => 'create',
        ]);
    }

    /** POST /admin/posts/{type?} */
    public function store(Request $request, string $type = 'post')
    {
        $data = $this->validated($request, null, $type);

        return DB::transaction(function () use ($request, $data, $type) {
            $post = new Post($data + [
                'type' => $type,
                'author_id' => auth()->id(),
            ]);
            $post->setSlugIfEmpty();
            $post->save();

            // Taxonomies
            $this->syncCategories($post, $request->input('category_ids', []));
            $this->syncTags($post, (string) $request->input('tags', ''));

            // Featured + gallery
            $post->featured_media_id = $request->input('featured_media_id');
            $post->save();

            $this->syncGallery($post, $request->input('gallery_ids', []));

            // Meta
            $this->syncMeta($post, $request->input('meta', []));

            // Revision
            $this->snapshot($post);

            return redirect()
                ->route('admin.posts.index', ['type' => $type])
                ->with('status', 'Created');
        });
    }

    /** GET /admin/posts/{post}/edit/{type?} */
    public function edit(Post $post, string $type = 'post')
    {
        abort_unless($post->type === $type, 404);

        $categoryTax = Taxonomy::where('slug', 'category')->first();
        $tagTax = Taxonomy::where('slug', 'post_tag')->first();

        $categories = Term::forTaxonomy('category')->orderBy('name')->get();
        $tags = Term::forTaxonomy('post_tag')->orderBy('name')->get();

        return view('admin.posts.edit', [
            'type' => $type,
            'post' => $post,
            'categoryTax' => $categoryTax,
            'tagTax' => $tagTax,
            'categories' => $categories,
            'tags' => $tags,
            'mode' => 'edit',
        ]);
    }

    /** PUT /admin/posts/{post}/{type?} */
    public function update(Request $request, Post $post, string $type = 'post')
    {
        abort_unless($post->type === $type, 404);

        $data = $this->validated($request, $post->id, $type);

        return DB::transaction(function () use ($request, $post, $data, $type) {
            $post->fill($data);
            if (!$post->slug) {
                $post->setSlugIfEmpty();
            }
            $post->save();

            $this->syncCategories($post, $request->input('category_ids', []));
            $this->syncTags($post, (string) $request->input('tags', ''));

            $post->featured_media_id = $request->input('featured_media_id');
            $post->save();

            $this->syncGallery($post, $request->input('gallery_ids', []));
            $this->syncMeta($post, $request->input('meta', []));

            $this->snapshot($post);

            return redirect()
                ->route('admin.posts.index', ['type' => $type])
                ->with('status', 'Updated');
        });
    }

    /** DELETE /admin/posts/{post}/{type?} */
    public function destroy(Post $post, string $type = 'post')
    {
        abort_unless($post->type === $type, 404);

        $post->delete();

        return redirect()
            ->route('admin.posts.index', ['type' => $type])
            ->with('status', 'Deleted');
    }

    /* ===================== helpers ===================== */

    protected function validated(Request $request, ?int $postId, string $type): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('posts', 'slug')->ignore($postId)],
            'content' => ['nullable', 'string'],
            'excerpt' => ['nullable'],
            'template' => ['nullable', 'string', 'max:128'],
            'status' => ['required', 'in:draft,pending,published'],
            'visibility' => ['required', 'in:public,private,password'],
            'password' => ['nullable', 'string', 'max:128'],
            'published_at' => ['nullable', 'date'],
            'format' => ['nullable', 'in:standard,gallery,image,video,quote,link,status,audio,chat'],
            'is_sticky' => ['boolean'],
            'allow_comments' => ['boolean'],
            'allow_pingbacks' => ['boolean'],
            'author_id' => ['nullable', 'integer'],
            'featured_media_id' => ['nullable', 'integer', 'exists:media,id'],
        ]);
    }

    /** Replace categories for the post (keep existing tags) */
    protected function syncCategories(Post $post, array $ids): void
    {
        // Only keep IDs that are *actually* category terms
        $termIds = Term::select('terms.id')
            ->join('term_taxonomies as tt', 'tt.term_id', '=', 'terms.id')
            ->where('tt.taxonomy', 'category')
            ->whereIn('terms.id', $ids ?: [])
            ->pluck('terms.id')
            ->all();

        // Keep current tags
        $currentTagIds = $post->terms()
            ->join('term_taxonomies as tt', 'tt.term_id', '=', 'terms.id')
            ->where('tt.taxonomy', 'post_tag')
            ->pluck('terms.id')
            ->all();

        $post->terms()->sync(array_unique(array_merge($termIds, $currentTagIds)));
    }

    /** Replace tags for the post (keep existing categories) */
    protected function syncTags(Post $post, string $csv): void
    {
        $names = collect(explode(',', $csv))
            ->map(fn($s) => trim($s))
            ->filter()
            ->take(50);

        $tagIds = [];

        foreach ($names as $name) {
            $slug = Str::slug($name);

            // Create/find term
            $term = Term::firstOrCreate(['slug' => $slug], ['name' => $name]);

            // Ensure it has a 'post_tag' term_taxonomy row
            TermTaxonomy::firstOrCreate([
                'term_id' => $term->id,
                'taxonomy' => 'post_tag',
            ]);

            $tagIds[] = $term->id;
        }

        // Keep current categories
        $currentCatIds = $post->terms()
            ->join('term_taxonomies as tt', 'tt.term_id', '=', 'terms.id')
            ->where('tt.taxonomy', 'category')
            ->pluck('terms.id')
            ->all();

        $post->terms()->sync(array_unique(array_merge($tagIds, $currentCatIds)));
    }

    protected function syncGallery(Post $post, array $ids): void
    {
        $sync = [];
        foreach (array_values($ids) as $i => $id) {
            $sync[$id] = ['role' => 'gallery', 'sort_order' => $i + 1];
        }
        $post->gallery()->sync($sync);
    }

    protected function syncMeta(Post $post, array $meta): void
    {
        foreach ($meta as $key => $value) {
            PostMeta::updateOrCreate(
                ['post_id' => $post->id, 'key' => $key],
                ['value' => $value]
            );
        }
    }

    protected function snapshot(Post $post): void
    {
        $data = [
            'post' => $post->toArray(),
            'meta' => $post->meta()->get(['key', 'value'])->toArray(),
            'terms' => $post->terms()->pluck('terms.id')->all(),
            'gallery' => $post->gallery()->pluck('media.id')->all(),
        ];

        PostRevision::create([
            'post_id' => $post->id,
            'user_id' => auth()->id(),
            'data' => $data,
        ]);
    }
}