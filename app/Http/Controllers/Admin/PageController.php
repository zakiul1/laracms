<?php

namespace App\Http\Controllers\Admin;

use App\Models\Post;
use App\Models\TermTaxonomy;

class PageController extends BaseContentController
{
    protected function contentType(): string
    {
        return 'page';
    }

    /**
     * Override: supply templates (and an empty categoriesTree) to the view.
     */
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
            'post' => $page,             // the form expects `post`
            'type' => 'page',
            'categoriesTree' => [],                // pages don't use categories
            'templates' => $this->templateOptions('page'),
        ]);
    }

    /**
     * Override: load relations + supply templates.
     */
    public function edit(Post $page)
    {
        abort_unless($page->type === 'page', 404);

        $page->load(['seo', 'metas', 'featuredMedia', 'gallery', 'revisions']);

        return view('admin.pages.edit', [
            'post' => $page,             // the form expects `post`
            'type' => 'page',
            'categoriesTree' => [],                // pages don't use categories
            'templates' => $this->templateOptions('page'),
        ]);
    }

    /**
     * Provide template dropdown options for pages.
     * Reads from config('theme.templates.page') or returns [].
     * Shape: ['template-key' => 'Human Label']
     */
    protected function templateOptions(string $type): array
    {
        $cfg = config("theme.templates.$type");
        return is_array($cfg) && !empty($cfg) ? $cfg : [];
    }

    /**
     * (Not used for pages) — kept for parity if you later want page hierarchies.
     * Each node: ['id','term_id','name','slug','parent','depth']
     */
    protected function categoriesTree(): array
    {
        $rows = TermTaxonomy::with('term')
            ->where('taxonomy', 'category')
            ->orderBy('parent')
            ->orderBy('id')
            ->get();

        $byParent = [];
        foreach ($rows as $r) {
            $byParent[(int) ($r->parent ?? 0)][] = $r;
        }

        $out = [];
        $walk = function (int $parent, int $depth) use (&$walk, &$out, $byParent) {
            if (!isset($byParent[$parent]))
                return;
            foreach ($byParent[$parent] as $r) {
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

        $walk(0, 0);
        return $out;
    }
}