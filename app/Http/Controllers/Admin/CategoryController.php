<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Taxonomy;
use App\Models\Term;
use App\Models\TermMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    protected function taxonomy(): Taxonomy
    {
        return Taxonomy::firstOrCreate(['slug' => 'category'], ['label' => 'Categories', 'hierarchical' => true]);
    }

    public function index()
    {
        $tax = $this->taxonomy();
        $items = Term::with('parent')->where('taxonomy_id', $tax->id)->orderBy('name')->get();
        $all = $items; // for parent select
        return view('admin.categories.index', compact('items', 'all'));
    }

    public function store(Request $r)
    {
        $tax = $this->taxonomy();
        $data = $r->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
        ]);

        $slug = $data['slug'] ?: Str::slug($data['name']);
        $term = Term::firstOrCreate(
            ['taxonomy_id' => $tax->id, 'slug' => $slug],
            ['name' => $data['name'], 'parent_id' => $data['parent_id'] ?? null]
        );

        if (!empty($data['description'])) {
            TermMeta::updateOrCreate(['term_id' => $term->id, 'key' => 'description'], ['value' => $data['description']]);
        }

        return back()->with('status', 'Category created');
    }

    public function edit(Term $term)
    {
        abort_unless($term->taxonomy?->slug === 'category', 404);
        $all = Term::where('taxonomy_id', $term->taxonomy_id)->where('id', '!=', $term->id)->orderBy('name')->get();
        return view('admin.categories.edit', compact('term', 'all'));
    }

    public function update(Request $r, Term $term)
    {
        abort_unless($term->taxonomy?->slug === 'category', 404);
        $data = $r->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'different:id'],
            'description' => ['nullable', 'string'],
        ]);
        $term->update([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'parent_id' => $data['parent_id'] ?? null,
        ]);
        TermMeta::updateOrCreate(['term_id' => $term->id, 'key' => 'description'], ['value' => $data['description'] ?? '']);

        return redirect()->route('admin.categories.index')->with('status', 'Category updated');
    }

    public function destroy(Term $term)
    {
        abort_unless($term->taxonomy?->slug === 'category', 404);
        $term->delete();
        return back()->with('status', 'Category deleted');
    }
}