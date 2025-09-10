<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\TermTaxonomy;
use Illuminate\Http\Request;

class FrontController extends Controller
{
    /**
     * Single page/post by slug.
     * Tries 'page' first, then 'post'. Only shows 'published'.
     */
    public function single(Request $request, string $slug)
    {
        // 1) Page
        $page = Post::query()
            ->where('type', 'page')
            ->where('slug', $slug)
            ->where('status', 'published')
            ->first();

        if ($page) {
            return view(theme_view('pages.show'), ['post' => $page]);
        }

        // 2) Post
        $post = Post::query()
            ->where('type', 'post')
            ->where('slug', $slug)
            ->where('status', 'published')
            ->first();

        if ($post) {
            return view(theme_view('posts.show'), ['post' => $post]);
        }

        // 3) Not found
        abort(404);
    }

    /**
     * Category archive (optional but handy).
     * /category/{slug}
     */
    public function category(string $slug)
    {
        $tt = TermTaxonomy::with('term')
            ->where('taxonomy', 'category')
            ->whereHas('term', fn($q) => $q->where('slug', $slug))
            ->firstOrFail();

        // Get published posts in this category
        $postIds = \DB::table('term_relationships')
            ->where('term_taxonomy_id', $tt->id)
            ->pluck('object_id');

        $posts = Post::whereIn('id', $postIds)
            ->where('type', 'post')
            ->where('status', 'published')
            ->latest('published_at')
            ->paginate(10);

        return view(theme_view('archives.category'), [
            'taxonomy' => $tt,
            'posts' => $posts,
        ]);
    }
}