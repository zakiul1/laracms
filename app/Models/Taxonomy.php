<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Taxonomy extends Model
{
    use HasFactory;

    protected $table = 'taxonomies';

    protected $fillable = [
        'name',
        'slug',
        'description',
        // (optional) 'hierarchical', 'label', etc. if you have those columns
    ];

    /**
     * Link to the intermediate rows (term_taxonomies) using slug -> taxonomy.
     */
    public function termTaxonomies(): HasMany
    {
        return $this->hasMany(TermTaxonomy::class, 'taxonomy', 'slug');
    }

    /**
     * All Terms in this taxonomy, via the term_taxonomies table.
     * Pivot table: term_taxonomies (taxonomy -> slug, term_id -> terms.id)
     */
    public function terms(): BelongsToMany
    {
        return $this->belongsToMany(
            Term::class,
            (new TermTaxonomy())->getTable(), // pivot: term_taxonomies
            'taxonomy',  // foreignPivotKey on pivot referencing this model
            'term_id',   // relatedPivotKey on pivot referencing Term
            'slug',      // parentKey on this model
            'id'         // relatedKey on Term
        );
    }
}