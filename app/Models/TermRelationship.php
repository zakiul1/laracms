<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TermRelationship extends Model
{
    protected $table = 'term_relationships';

    protected $fillable = [
        'object_id',
        'term_taxonomy_id',
        'sort_order',
    ];

    public function termTaxonomy(): BelongsTo
    {
        return $this->belongsTo(TermTaxonomy::class, 'term_taxonomy_id');
    }

    // Convenience passthrough
    public function term()
    {
        return $this->termTaxonomy?->term;
    }
}