<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Post extends Model
{
    protected $fillable = ['type', 'title', 'slug', 'content', 'meta', 'status', 'published_at', 'user_id'];
    protected $casts = ['meta' => 'array', 'published_at' => 'datetime'];

    public function taxonomies(): BelongsToMany
    {
        return $this->belongsToMany(TermTaxonomy::class, 'post_term');
    }
}