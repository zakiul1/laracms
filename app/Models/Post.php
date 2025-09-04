<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Post extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'format',
        'title',
        'slug',
        'content',
        'excerpt',
        'template',
        'author_id',
        'status',
        'visibility',
        'password',
        'published_at',
        'is_sticky',
        'allow_comments',
        'allow_pingbacks',
        'featured_media_id',
    ];

    protected $casts = [
        // Excerpt is plain text in DB
        'excerpt' => 'string',
        'published_at' => 'datetime',
        'is_sticky' => 'bool',
        'allow_comments' => 'bool',
        'allow_pingbacks' => 'bool',
    ];

    /* -----------------------------------------------------------------
     | Relationships
     * ----------------------------------------------------------------*/

    // If you still use post_term somewhere
    public function terms(): BelongsToMany
    {
        return $this->belongsToMany(Term::class, 'post_term');
    }

    public function meta(): HasMany
    {
        return $this->hasMany(PostMeta::class);
    }

    public function metas(): HasMany
    {
        return $this->hasMany(PostMeta::class);
    }

    public function featured(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }

    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }

    /**
     * Featured Images gallery (multiple) used by the editor.
     * Stored in post_media with role='featured' and sequential 'position'.
     */
    public function gallery(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'post_media', 'post_id', 'media_id')
            ->withPivot(['role', 'position'])
            ->wherePivot('role', 'featured')
            ->orderBy('post_media.position');
    }

    public function seo(): HasOne
    {
        return $this->hasOne(PostSeo::class, 'post_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(PostRevision::class)->latest();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /* -----------------------------------------------------------------
     | Scopes & helpers
     * ----------------------------------------------------------------*/

    public function scopeType($q, string $type)
    {
        return $q->where('type', $type);
    }

    public function setSlugIfEmpty(): void
    {
        if (!$this->slug) {
            $this->slug = \Str::slug($this->title) ?: \Str::random(8);
        }
    }

    protected static function booted(): void
    {
        static::saving(function (self $post) {
            $post->setSlugIfEmpty();
        });
    }
}