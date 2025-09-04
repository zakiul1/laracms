<?php

namespace App\Http\Controllers\Admin;

use App\Models\Post;
use App\Models\TermTaxonomy;
use Illuminate\Support\Facades\DB;      // ⬅️ add this
use Illuminate\Support\Facades\Schema;

class PostController extends BaseContentController
{
    protected function contentType(): string
    {
        return 'post';
    }

    public function create()
    {
        $post = new Post([
            'type' => 'post',
            'status' => 'draft',
            'visibility' => 'public',
            'is_sticky' => false,
            'allow_comments' => true,
        ]);

        return view('admin.posts.create', [
            'post' => $post,
            'type' => 'post',
            'categoriesTree' => $this->categoriesTree(),
            'selectedCategoryIds' => [],                     // ⬅️ none on create
            'templates' => $this->templateOptions('post'),
        ]);
    }

    public function edit(Post $post)
    {
        abort_unless($post->type === 'post', 404);

        $post->load(['seo', 'metas', 'featuredMedia', 'gallery', 'revisions']);

        // ⬅️ find already-selected category term_taxonomy IDs for this post
        $selectedCategoryIds = DB::table('term_relationships as tr')
            ->join('term_taxonomies as tt', 'tt.id', '=', 'tr.term_taxonomy_id')
            ->where('tr.object_id', $post->id)
            ->where('tt.taxonomy', 'category')
            ->pluck('tr.term_taxonomy_id')
            ->map(fn($v) => (int) $v)
            ->all();

        return view('admin.posts.edit', [
            'post' => $post,
            'type' => 'post',
            'categoriesTree' => $this->categoriesTree(),
            'selectedCategoryIds' => $selectedCategoryIds,   // ⬅️ used by the form
            'templates' => $this->templateOptions('post'),
        ]);
    }

    /**
     * Build categories list safely:
     * - Works if parent column is 'parent' or 'parent_id' or missing (flat list)
     * - Does NOT require a TermTaxonomy::term() relation
     * - Prevents cycles/infinite recursion
     */
    protected function categoriesTree(): array
    {
        // Detect hierarchy column
        $cols = Schema::getColumnListing('term_taxonomies');
        $parentCol = in_array('parent', $cols, true)
            ? 'parent'
            : (in_array('parent_id', $cols, true) ? 'parent_id' : null);

        // Only eager-load 'term' if the relation actually exists
        $hasTermRelation = method_exists(TermTaxonomy::class, 'term');

        $rows = TermTaxonomy::query()
            ->where('taxonomy', 'category')
            ->when($hasTermRelation, fn($q) => $q->with('term'))
            ->get();

        // If no parent column, return a flat list sorted by name
        if (!$parentCol) {
            return $rows->sortBy(function ($r) use ($hasTermRelation) {
                return $hasTermRelation ? (optional($r->term)->name ?? '') : '';
            })
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

        // Build adjacency list: parent_id => [nodes...]
        $childrenByParent = [];
        foreach ($rows as $r) {
            $p = (int) ($r->{$parentCol} ?? 0);
            $childrenByParent[$p][] = $r;
        }

        // Depth-first walk with cycle protection
        $out = [];
        $visited = [];

        $walk = function (int $parentId, int $depth) use (&$walk, &$out, &$visited, $childrenByParent, $parentCol, $hasTermRelation) {
            foreach ($childrenByParent[$parentId] ?? [] as $r) {
                if (isset($visited[$r->id])) {
                    continue; // avoid cycles
                }
                $visited[$r->id] = true;

                $out[] = [
                    'id' => (int) $r->id,
                    'term_id' => (int) $r->term_id,
                    'name' => $hasTermRelation ? (optional($r->term)->name ?? ('Term #' . $r->term_id)) : ('Term #' . $r->term_id),
                    'slug' => $hasTermRelation ? optional($r->term)->slug : null,
                    'parent' => (int) ($r->{$parentCol} ?? 0),
                    'depth' => $depth,
                ];

                // Recurse into CHILD id
                $walk((int) $r->id, $depth + 1);
            }
        };

        // Roots
        $walk(0, 0);

        // Orphans fallback
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
}