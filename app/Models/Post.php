<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str; // ⬅️ for slug helpers

// Spatie Media Library
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;

// Your optimized conversions trait (from app/Support/Media/HasOptimizedImages.php)
use App\Support\Media\HasOptimizedImages;

class Post extends Model implements HasMedia
{
    use SoftDeletes;
    use HasOptimizedImages; // adds media collection 'images' + WebP/srcset conversions

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
        'excerpt' => 'string',
        'published_at' => 'datetime',
        'is_sticky' => 'bool',
        'allow_comments' => 'bool',
        'allow_pingbacks' => 'bool',
    ];

    /* -----------------------------------------------------------------
     | Relationships (existing)
     * ----------------------------------------------------------------*/

    public function terms(): BelongsToMany
    {
        return $this->belongsToMany(Term::class, 'post_term');
    }

    public function meta(): HasMany
    {
        return $this->hasMany(PostMeta::class);
    }

    // Kept for backward compatibility if some views/calls use metas()
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
     | Spatie Media (helpers)
     * ----------------------------------------------------------------*/

    public function spatieFeaturedMedia(): ?SpatieMedia
    {
        return $this->getFirstMedia('images');
    }

    public function spatieImages()
    {
        return collect($this->getMedia('images'));
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
            $this->slug = Str::slug($this->title) ?: Str::random(8);
        }
    }

    /**
     * Ensure slug is unique within the same `type` by appending -2, -3, ...
     * Includes soft-deleted rows to match the DB unique index behavior.
     */
    public static function uniqueSlug(string $value, string $type, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: Str::random(8);
        $slug = $base;

        $existing = static::withTrashed()
            ->where('type', $type)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', 'like', $base . '%')
            ->pluck('slug')
            ->all();

        if (!in_array($slug, $existing, true)) {
            return $slug;
        }

        $n = 2;
        while (in_array($candidate = "{$base}-{$n}", $existing, true)) {
            $n++;
        }
        return $candidate;
    }

    protected static function booted(): void
    {
        static::saving(function (self $post) {
            // Normalize provided slug or build from title, then make unique per type
            $raw = $post->slug ?: $post->title;
            $post->slug = static::uniqueSlug($raw, $post->type ?? 'post', $post->id);
        });
    }
}