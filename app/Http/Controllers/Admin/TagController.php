<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Term;
use App\Models\TermTaxonomy;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function suggest(Request $r)
    {
        $q = trim((string) $r->query('q', ''));
        $tags = Term::query()
            ->select('terms.id', 'terms.name', 'terms.slug')
            ->join('term_taxonomies', 'term_taxonomies.term_id', '=', 'terms.id')
            ->where('term_taxonomies.taxonomy', 'post_tag')
            ->when($q !== '', fn($x) => $x->where('terms.name', 'like', "%$q%"))
            ->orderBy('terms.name')
            ->limit(20)
            ->get();

        return response()->json($tags);
    }
}