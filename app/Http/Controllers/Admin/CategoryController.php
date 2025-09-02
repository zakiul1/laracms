<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Term;
use App\Models\TermMeta;
use App\Models\TermTaxonomy;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * List categories.
     * Pull terms that have a term_taxonomies row with taxonomy='category'.
     */
    public function index()
    {
        $items = Term::with('parent')
            ->forTaxonomy('category')      // <- uses term_taxonomies
            ->orderBy('name')
            ->get();

        $all = $items; // for parent select
        return view('admin.categories.index', compact('items', 'all'));
    }

    /**
     * Create a category.
     * Creates the term (if needed) and ensures a term_taxonomies row for 'category'.
     */
    public function store(Request $r)
    {
        $data = $r->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
        ]);

        $slug = $data['slug'] ?: Str::slug($data['name']);

        // Ensure unique slug (simple auto-increment if taken)
        $base = $slug;
        $i = 2;
        while (Term::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        // 1) Create/find term
        $term = Term::firstOrCreate(
            ['slug' => $slug],
            ['name' => $data['name'], 'parent_id' => $data['parent_id'] ?? null]
        );

        // 2) Ensure a term_taxonomies row for taxonomy='category'
        TermTaxonomy::firstOrCreate(
            ['term_id' => $term->id, 'taxonomy' => 'category'],
            ['parent_id' => null]
        );

        // Optional description stored as term meta (keeps your current approach)
        if (!empty($data['description'])) {
            TermMeta::updateOrCreate(
                ['term_id' => $term->id, 'key' => 'description'],
                ['value' => $data['description']]
            );
        }

        return back()->with('status', 'Category created');
    }

    /**
     * Edit form.
     * Guard: only terms that belong to taxonomy='category'.
     */
    public function edit(Term $term)
    {
        $isCategory = TermTaxonomy::where('term_id', $term->id)
            ->where('taxonomy', 'category')
            ->exists();

        abort_unless($isCategory, 404);

        $all = Term::forTaxonomy('category')
            ->where('id', '!=', $term->id)
            ->orderBy('name')
            ->get();

        return view('admin.categories.edit', compact('term', 'all'));
    }

    /**
     * Update a category (term data + description meta).
     */
    public function update(Request $r, Term $term)
    {
        $isCategory = TermTaxonomy::where('term_id', $term->id)
            ->where('taxonomy', 'category')
            ->exists();

        abort_unless($isCategory, 404);

        $data = $r->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'different:id'],
            'description' => ['nullable', 'string'],
        ]);

        // keep slug unique
        if (Term::where('slug', $data['slug'])->where('id', '!=', $term->id)->exists()) {
            return back()->withErrors(['slug' => 'Slug already taken.'])->withInput();
        }

        $term->update([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'parent_id' => $data['parent_id'] ?? null,
        ]);

        TermMeta::updateOrCreate(
            ['term_id' => $term->id, 'key' => 'description'],
            ['value' => $data['description'] ?? '']
        );

        // guarantee a TT row still exists
        TermTaxonomy::firstOrCreate(
            ['term_id' => $term->id, 'taxonomy' => 'category'],
            ['parent_id' => null]
        );

        return redirect()->route('admin.categories.index')->with('status', 'Category updated');
    }

    /**
     * Delete a category (term + its 'category' taxonomy row).
     */
    public function destroy(Term $term)
    {
        $isCategory = TermTaxonomy::where('term_id', $term->id)
            ->where('taxonomy', 'category')
            ->exists();

        abort_unless($isCategory, 404);

        // Remove the 'category' taxonomy row(s) for this term
        TermTaxonomy::where('term_id', $term->id)
            ->where('taxonomy', 'category')
            ->delete();

        // If the term isn't used by any other taxonomy, you may delete it.
        $stillUsed = TermTaxonomy::where('term_id', $term->id)->exists();
        if (!$stillUsed) {
            $term->delete();
        }

        return back()->with('status', 'Category deleted');
    }
}