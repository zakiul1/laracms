<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Term;
use App\Models\TermTaxonomy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TaxonomyQuickController extends Controller
{
    public function quickCategory(Request $request)
    {
        // Validate inputs
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'parent' => ['nullable', 'integer'], // you can add: Rule::exists('term_taxonomies', 'id')
        ]);

        $name = trim($data['name']);
        $parentTtxId = (int) ($data['parent'] ?? 0);

        // Create/find term by slug
        $term = Term::firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name]
        );

        // Detect parent column on term_taxonomies
        $parentCol = Schema::hasColumn('term_taxonomies', 'parent')
            ? 'parent'
            : (Schema::hasColumn('term_taxonomies', 'parent_id') ? 'parent_id' : null);

        // Create/find taxonomy row
        $attrs = ['term_id' => $term->id, 'taxonomy' => 'category'];
        $values = ['description' => null];

        if ($parentCol) {
            $values = array_merge($values, [$parentCol => $parentTtxId ?: 0]);
        }

        $ttx = TermTaxonomy::firstOrCreate($attrs, $values);

        // If it already existed, update parent if changed
        if ($parentCol && (int) $ttx->{$parentCol} !== ($parentTtxId ?: 0)) {
            $ttx->{$parentCol} = $parentTtxId ?: 0;
            $ttx->save();
        }

        return response()->json([
            'ok' => true,
            'id' => (int) $ttx->id,     // term_taxonomy id
            'term_id' => (int) $term->id,
        ], 201);
    }
}