<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use SoftDeletes;

    protected $table = 'media';

    protected $fillable = [
        'created_by',
        'disk',
        'path',
        'filename',
        'mime',
        'size',
        'width',
        'height',
        'title',
        'alt',
        'caption',
    ];

    protected $appends = ['url'];

    public function getUrlAttribute(): ?string
    {
        return $this->path
            ? Storage::disk($this->disk ?: 'public')->url($this->path)
            : null;
    }

    /** raw pivot rows for this media */
    public function termRelationships(): HasMany
    {
        return $this->hasMany(TermRelationship::class, 'object_id', 'id');
    }

    /** all TermTaxonomy rows linked to this media */
    public function termTaxonomies(): BelongsToMany
    {
        return $this->belongsToMany(
            TermTaxonomy::class,
            'term_relationships', // <-- pivot table name (string)
            'object_id',
            'term_taxonomy_id'
        );
    }

    /** Terms in 'media_category' taxonomy (returns Term models) */
    public function categories(): BelongsToMany
    {
        $tr = (new TermRelationship())->getTable(); // term_relationships
        $tt = (new TermTaxonomy())->getTable();     // term_taxonomies
        $t = (new Term())->getTable();             // terms

        return $this->belongsToMany(
            Term::class,
            $tr,
            'object_id',
            'term_taxonomy_id'
        )
            ->join($tt, "{$tr}.term_taxonomy_id", '=', "{$tt}.id")
            ->join($t, "{$tt}.term_id", '=', "{$t}.id")
            ->where("{$tt}.taxonomy", 'media_category')
            ->select("{$t}.*");
    }

    // ---------- Helpers ----------

    public function assignCategoryByTT(int $termTaxonomyId): void
    {
        TermRelationship::firstOrCreate([
            'object_id' => $this->id,
            'term_taxonomy_id' => $termTaxonomyId,
        ]);
    }

    public function assignCategoryBySlug(string $termSlug): void
    {
        $ttId = TermTaxonomy::where('taxonomy', 'media_category')
            ->whereHas('term', fn($q) => $q->where('slug', $termSlug))
            ->value('id');

        if ($ttId) {
            $this->assignCategoryByTT((int) $ttId);
        }
    }

    public function moveToCategoryByTT(int $termTaxonomyId): void
    {
        $tr = (new TermRelationship())->getTable();
        $tt = (new TermTaxonomy())->getTable();

        TermRelationship::where("{$tr}.object_id", $this->id)
            ->whereIn("{$tr}.term_taxonomy_id", function ($q) use ($tt) {
                $q->select('id')->from($tt)->where('taxonomy', 'media_category');
            })
            ->delete();

        $this->assignCategoryByTT($termTaxonomyId);
    }

    // ---------- Scopes ----------

    public function scopeQuickSearch($query, ?string $s)
    {
        if (!$s)
            return $query;
        $s = trim($s);
        return $query->where(function ($qq) use ($s) {
            $qq->where('filename', 'like', "%{$s}%")
                ->orWhere('mime', 'like', "%{$s}%")
                ->orWhere('title', 'like', "%{$s}%")
                ->orWhere('alt', 'like', "%{$s}%")
                ->orWhere('caption', 'like', "%{$s}%");
        });
    }

    public function scopeInCategoryTT($query, int $termTaxonomyId)
    {
        $tr = (new TermRelationship())->getTable();
        return $query->whereIn('id', function ($q) use ($tr, $termTaxonomyId) {
            $q->select('object_id')->from($tr)->where('term_taxonomy_id', $termTaxonomyId);
        });
    }

    public function scopeInCategorySlug($query, string $termSlug)
    {
        $ttId = TermTaxonomy::where('taxonomy', 'media_category')
            ->whereHas('term', fn($q) => $q->where('slug', $termSlug))
            ->value('id');

        return $ttId
            ? $this->scopeInCategoryTT($query, (int) $ttId)
            : $query->whereRaw('1=0');
    }
}