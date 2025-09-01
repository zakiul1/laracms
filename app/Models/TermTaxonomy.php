<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TermTaxonomy extends Model
{
    protected $table = 'term_taxonomies';

    // NO 'name' column here — name/slug live on terms
    protected $fillable = [
        'term_id',
        'taxonomy',      // 'category', 'tag', 'media_category', etc.
        'description',
        'parent_id',
        'count',
    ];

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'term_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function relationships(): HasMany
    {
        return $this->hasMany(TermRelationship::class, 'term_taxonomy_id');
    }

    /** Scope: filter by taxonomy slug (defaults to media_category if omitted) */
    public function scopeTaxonomy($query, string $slug = 'media_category')
    {
        return $query->where('taxonomy', $slug);
    }

    /** Convenience: only media categories */
    public function scopeMediaCategory($query)
    {
        return $query->where('taxonomy', 'media_category');
    }
}