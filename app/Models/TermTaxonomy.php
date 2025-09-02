<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TermTaxonomy extends Model
{
    protected $table = 'term_taxonomies';

    // Disable if your table has no created_at/updated_at columns
    public $timestamps = false;

    protected $fillable = [
        'term_id',
        'taxonomy',      // 'category', 'tag', 'media_category', etc.
        'description',
        'parent_id',
        'count',
    ];

    protected $casts = [
        'term_id' => 'integer',
        'parent_id' => 'integer',
        'count' => 'integer',
    ];

    // Optional: make name/slug accessible directly on the model
    protected $appends = ['name', 'slug'];

    public function getNameAttribute(): ?string
    {
        return $this->relationLoaded('term') ? ($this->term->name ?? null) : ($this->term()->value('name'));
    }

    public function getSlugAttribute(): ?string
    {
        return $this->relationLoaded('term') ? ($this->term->slug ?? null) : ($this->term()->value('slug'));
    }

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