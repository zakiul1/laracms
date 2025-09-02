<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Term extends Model
{
    use HasFactory;

    protected $table = 'terms';

    // Keep only actual columns on `terms`
    protected $fillable = [
        'name',
        'slug',
        'parent_id', // if you're storing hierarchy on `terms`
    ];

    /** Parent term (if you keep parent_id on `terms`) */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** Children terms */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** Optional term meta */
    public function meta(): HasMany
    {
        return $this->hasMany(TermMeta::class);
    }

    /** All taxonomy rows for this term (rows in term_taxonomies) */
    public function termTaxonomies(): HasMany
    {
        return $this->hasMany(TermTaxonomy::class, 'term_id');
    }

    /**
     * Compatibility shim: a single "taxonomy" relation.
     * Returns the latest TermTaxonomy row for this term.
     * Old code like $term->taxonomy?->slug === 'category' will now work, where:
     *   - $term->taxonomy->taxonomy is the taxonomy string
     *   - $term->taxonomy->slug is an alias to that string (for older code)
     */
    public function taxonomy(): HasOne
    {
        // latestOfMany() needs a sortable column; id works fine
        return $this->hasOne(TermTaxonomy::class, 'term_id')
            ->latestOfMany()
            ->selectRaw('term_taxonomies.*, term_taxonomies.taxonomy as slug');
        // alias "taxonomy" -> "slug" so legacy comparisons using ->slug still pass
    }

    /**
     * Scope: filter terms that belong to a given taxonomy (via term_taxonomies).
     * Example: Term::forTaxonomy('category')->orderBy('name')->get();
     */
    public function scopeForTaxonomy($query, string $taxonomy)
    {
        return $query->whereIn('id', function ($q) use ($taxonomy) {
            $q->select('term_id')
                ->from((new TermTaxonomy())->getTable())
                ->where('taxonomy', $taxonomy);
        });
    }

    /** Media attached to this term in 'media_category' taxonomy */
    public function media(): BelongsToMany
    {
        $tr = (new TermRelationship())->getTable();
        $tt = (new TermTaxonomy())->getTable();

        $relation = $this->belongsToMany(
            Media::class,
            $tr,
            'term_taxonomy_id', // pivot key on TR
            'object_id'         // related key on TR
        );

        // constrain pivot rows to this term's TT rows for media_category
        $relation->whereIn("{$tr}.term_taxonomy_id", function ($q) use ($tt) {
            $q->select("{$tt}.id")
                ->from($tt)
                ->where("{$tt}.term_id", $this->getKey())
                ->where("{$tt}.taxonomy", 'media_category');
        });

        return $relation;
    }

    /** Posts attached to this term in 'category' taxonomy (if you use TT/TR for posts) */
    public function posts(): BelongsToMany
    {
        $tr = (new TermRelationship())->getTable();
        $tt = (new TermTaxonomy())->getTable();

        $relation = $this->belongsToMany(
            Post::class,
            $tr,
            'term_taxonomy_id',
            'object_id'
        )->withTimestamps();

        $relation->whereIn("{$tr}.term_taxonomy_id", function ($q) use ($tt) {
            $q->select("{$tt}.id")
                ->from($tt)
                ->where("{$tt}.term_id", $this->getKey())
                ->where("{$tt}.taxonomy", 'category');
        });

        return $relation;
    }
}