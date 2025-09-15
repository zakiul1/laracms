<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

// Spatie Media Library
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;

// Spatie Image enum (required by your version for fit())
use Spatie\Image\Enums\Fit;

// Your optimized conversions trait (defines 'images' etc. in some models)
use App\Support\Media\HasOptimizedImages as HasOptimizedImagesTrait;

class Post extends Model implements HasMedia
{
    use SoftDeletes;
    use InteractsWithMedia;

    // Alias trait methods so we can extend them
    use HasOptimizedImagesTrait {
        HasOptimizedImagesTrait::registerMediaCollections as protected optimizedRegisterMediaCollections;
        HasOptimizedImagesTrait::registerMediaConversions as protected optimizedRegisterMediaConversions;
    }

    protected $fillable = [
        'type','format','title','slug','content','excerpt','template','author_id',
        'status','visibility','password','published_at','is_sticky','allow_comments',
        'allow_pingbacks','featured_media_id',
    ];

    protected $casts = [
        'excerpt'         => 'string',
        'published_at'    => 'datetime',
        'is_sticky'       => 'bool',
        'allow_comments'  => 'bool',
        'allow_pingbacks' => 'bool',
    ];

    /* ------------------------------- Relations ------------------------------ */

    public function terms(): BelongsToMany { return $this->belongsToMany(Term::class, 'post_term'); }
    public function meta(): HasMany { return $this->hasMany(PostMeta::class); }
    public function metas(): HasMany { return $this->hasMany(PostMeta::class); } // back-compat

    public function featured(): BelongsTo { return $this->belongsTo(Media::class, 'featured_media_id'); }
    public function featuredMedia(): BelongsTo { return $this->belongsTo(Media::class, 'featured_media_id'); }

    /** Featured gallery stored in post_media with role='featured' */
    public function gallery(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'post_media', 'post_id', 'media_id')
            ->withPivot(['role','position'])
            ->wherePivot('role','featured')
            ->orderBy('post_media.position');
    }

    public function seo(): HasOne { return $this->hasOne(PostSeo::class, 'post_id'); }
    public function revisions(): HasMany { return $this->hasMany(PostRevision::class)->latest(); }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'author_id'); }

    /* ----------------------------- Spatie helpers --------------------------- */

    /** Prefer 'featured' collection; fall back to 'images' */
    public function spatieFeaturedMedia(): ?SpatieMedia
    {
        return $this->getFirstMedia('featured') ?: $this->getFirstMedia('images');
    }

    public function spatieImages() { return collect($this->getMedia('images')); }

    /** Ensure both 'images' and 'featured' exist (guard older Spatie that lacks hasMediaCollection) */
    public function registerMediaCollections(): void
    {
        if (method_exists($this, 'optimizedRegisterMediaCollections')) {
            $this->optimizedRegisterMediaCollections();
        }

        // images (enable responsive originals)
        if (!method_exists($this, 'hasMediaCollection') || !$this->hasMediaCollection('images')) {
            $this->addMediaCollection('images')
                ->acceptsMimeTypes(['image/jpeg','image/png','image/gif','image/webp','image/avif'])
                ->withResponsiveImages(); // ✅ key addition
        }

        // featured (single file, also responsive)
        if (!method_exists($this, 'hasMediaCollection') || !$this->hasMediaCollection('featured')) {
            $this->addMediaCollection('featured')
                ->singleFile()
                ->withResponsiveImages(); // ✅ key addition
        }
    }

    /**
     * WordPress-like responsive widths for BOTH 'images' and 'featured'.
     * Creates w{width} and w{width}_webp (320 … 1920) + md/lg/xl aliases.
     */
    public function registerMediaConversions(?SpatieMedia $media = null): void
    {
        if (method_exists($this, 'optimizedRegisterMediaConversions')) {
            $this->optimizedRegisterMediaConversions($media);
        }

        $widths = [320, 480, 640, 768, 1024, 1280, 1536, 1920];

        foreach ($widths as $w) {
            // ✅ Force JPEG for width-based set
            $this->addMediaConversion('w' . $w)
                ->format('jpg')
                ->width($w)
                ->performOnCollections('images', 'featured')
                ->nonQueued();

            // ✅ WebP counterpart
            $this->addMediaConversion('w' . $w . '_webp')
                ->format('webp')
                ->width($w)
                ->performOnCollections('images', 'featured')
                ->nonQueued();
        }

        // Thumb (enum-based fit)
        $this->addMediaConversion('thumb_webp')
            ->format('webp')
            ->fit(Fit::Crop, 300, 300)
            ->performOnCollections('images', 'featured')
            ->nonQueued();

        // (Optional) keep md/lg/xl aliases as JPEG + WebP
        $this->addMediaConversion('md')->format('jpg')->width(640)->performOnCollections('images', 'featured')->nonQueued();
        $this->addMediaConversion('lg')->format('jpg')->width(960)->performOnCollections('images', 'featured')->nonQueued();
        $this->addMediaConversion('xl')->format('jpg')->width(1200)->performOnCollections('images', 'featured')->nonQueued();
        $this->addMediaConversion('md-webp')->format('webp')->width(640)->performOnCollections('images', 'featured')->nonQueued();
        $this->addMediaConversion('lg-webp')->format('webp')->width(960)->performOnCollections('images', 'featured')->nonQueued();
        $this->addMediaConversion('xl-webp')->format('webp')->width(1200)->performOnCollections('images', 'featured')->nonQueued();
    }

    /* ------------------------------- Slugs ---------------------------------- */

    public function scopeType($q, string $type) { return $q->where('type', $type); }

    public function setSlugIfEmpty(): void
    {
        if (!$this->slug) $this->slug = Str::slug($this->title) ?: Str::random(8);
    }

    /** Unique slug within same `type` (includes soft-deleted rows) */
    public static function uniqueSlug(string $value, string $type, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: Str::random(8);
        $slug = $base;

        $existing = static::withTrashed()
            ->where('type', $type)
            ->when($ignoreId, fn($q) => $q->where('id','!=',$ignoreId))
            ->where('slug','like',$base.'%')
            ->pluck('slug')
            ->all();

        if (!in_array($slug, $existing, true)) return $slug;

        $n = 2;
        while (in_array($candidate = "{$base}-{$n}", $existing, true)) { $n++; }
        return $candidate;
    }

    protected static function booted(): void
    {
        static::saving(function (self $post) {
            $raw = $post->slug ?: $post->title;
            $post->slug = static::uniqueSlug($raw, $post->type ?? 'post', $post->id);
        });
    }
}