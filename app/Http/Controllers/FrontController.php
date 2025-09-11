<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\TermTaxonomy;
use Illuminate\Http\Request;
use App\Support\Appearance\ThemeManager;

class FrontController extends Controller
{
    /** Normalize admin-saved template values into a clean slug (e.g. "full-width") */
    protected function normalizeTemplate(?string $tpl): string
    {
        $tpl = trim((string) $tpl);

        // Remove extension if entered by mistake
        $tpl = preg_replace('/(\.blade)?\.php$/i', '', $tpl);

        // Convert path separators to dots, then remove leading namespaces if user pasted them
        $tpl = str_replace(['\\', '/'], '.', $tpl);
        $tpl = preg_replace('/^(theme::)?(pages\.templates\.|posts\.templates\.)/i', '', $tpl);

        return trim($tpl, '.');
    }

    public function single(Request $request, string $slug)
    {
        // ensure theme:: namespace points to the active theme each request
        app(ThemeManager::class)->rebindViewNamespace();

        // 1) Page
        $page = Post::query()
            ->where('type', 'page')
            ->where('slug', $slug)
            ->where('status', 'published')
            ->first();

        if ($page) {
            $tpl = $this->normalizeTemplate($page->template ?? '');
            if ($tpl !== '' && view()->exists("theme::pages.templates.$tpl")) {
                return view("theme::pages.templates.$tpl", ['post' => $page]);
            }
            return view(theme_view('pages.show'), ['post' => $page]);
        }

        // 2) Post
        $post = Post::query()
            ->where('type', 'post')
            ->where('slug', $slug)
            ->where('status', 'published')
            ->first();

        if ($post) {
            $tpl = $this->normalizeTemplate($post->template ?? '');
            if ($tpl !== '' && view()->exists("theme::posts.templates.$tpl")) {
                return view("theme::posts.templates.$tpl", ['post' => $post]);
            }
            return view(theme_view('posts.show'), ['post' => $post]);
        }

        // 3) Not found (themed 404 if present)
        if (view()->exists('theme::404')) {
            return response()->view('theme::404', [], 404);
        }
        abort(404);
    }

    public function category(string $slug)
    {
        app(ThemeManager::class)->rebindViewNamespace();

        $tt = TermTaxonomy::with('term')
            ->where('taxonomy', 'category')
            ->whereHas('term', fn($q) => $q->where('slug', $slug))
            ->firstOrFail();

        $postIds = \DB::table('term_relationships')
            ->where('term_taxonomy_id', $tt->id)
            ->pluck('object_id');

        $posts = Post::whereIn('id', $postIds)
            ->where('type', 'post')
            ->where('status', 'published')
            ->latest('published_at')
            ->paginate(10);

        $view = view()->exists('theme::archives.category')
            ? 'theme::archives.category'
            : (view()->exists('theme::archives.index') ? 'theme::archives.index' : theme_view('posts.index'));

        return view($view, [
            'taxonomy' => $tt,
            'posts' => $posts,
        ]);
    }
}