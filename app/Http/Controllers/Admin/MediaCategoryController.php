<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Term;
use App\Models\TermTaxonomy;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MediaCategoryController extends Controller
{
    protected string $taxonomy = 'media_category';

    public function index()
    {
        $cats = TermTaxonomy::with(['term', 'parent.term'])
            ->where('taxonomy', $this->taxonomy)
            ->orderBy('parent_id')
            ->orderBy('id')
            ->get();

        $parents = TermTaxonomy::with('term')
            ->where('taxonomy', $this->taxonomy)
            ->orderBy('id')
            ->get();

        return view('admin.media.categories.index', compact('cats', 'parents'));
    }

    public function create()
    {
        $parents = TermTaxonomy::with('term')
            ->where('taxonomy', $this->taxonomy)
            ->orderBy('id')
            ->get();

        return view('admin.media.categories.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140'],
            'parent_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $slug = $data['slug'] ?? Str::slug($data['name']);

        $term = Term::firstOrCreate(['slug' => $slug], ['name' => $data['name']]);

        TermTaxonomy::firstOrCreate(
            ['term_id' => $term->id, 'taxonomy' => $this->taxonomy],
            ['description' => $data['description'] ?? null, 'parent_id' => $data['parent_id'] ?? null]
        );

        return redirect()->route('admin.media.categories.index')->with('status', 'Category created.');
    }

    public function edit(TermTaxonomy $tt)
    {
        abort_unless($tt->taxonomy === $this->taxonomy, 404);

        $parents = TermTaxonomy::with('term')
            ->where('taxonomy', $this->taxonomy)
            ->where('id', '!=', $tt->id)
            ->orderBy('id')->get();

        return view('admin.media.categories.edit', compact('tt', 'parents'));
    }

    public function update(Request $request, TermTaxonomy $tt)
    {
        abort_unless($tt->taxonomy === $this->taxonomy, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140'],
            'parent_id' => ['nullable', 'integer', 'not_in:' . $tt->id],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $slug = $data['slug'] ?? Str::slug($data['name']);
        $tt->term->update(['name' => $data['name'], 'slug' => $slug]);

        $tt->update([
            'description' => $data['description'] ?? null,
            'parent_id' => $data['parent_id'] ?? null,
        ]);

        return redirect()->route('admin.media.categories.index')->with('status', 'Category updated.');
    }

    public function destroy(TermTaxonomy $tt)
    {
        abort_unless($tt->taxonomy === $this->taxonomy, 404);

        if (method_exists($tt, 'relationships') && $tt->relationships()->exists()) {
            return back()->with('error', 'Category is in use. Move items first.');
        }

        $tt->delete();

        return back()->with('status', 'Category deleted.');
    }

    // ----- NEW: JSON for SPA/Alpine -----
    public function json()
    {
        return TermTaxonomy::with(['term', 'parent.term'])
            ->where('taxonomy', $this->taxonomy)
            ->orderBy('parent_id')
            ->orderBy('id')
            ->get();
    }

    // ----- NEW: quick-create from upload modal -----
    public function quickCreate(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        $slug = Str::slug($data['name']);

        $term = Term::firstOrCreate(['slug' => $slug], ['name' => $data['name']]);

        $tt = TermTaxonomy::firstOrCreate(
            ['term_id' => $term->id, 'taxonomy' => $this->taxonomy],
            ['parent_id' => $data['parent_id'] ?? null]
        );

        return response()->json(['status' => 'ok', 'item' => $tt->load(['term', 'parent.term'])]);
    }
}