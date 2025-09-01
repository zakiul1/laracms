<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
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
        'excerpt' => 'array',
        'published_at' => 'datetime',
        'is_sticky' => 'bool',
        'allow_comments' => 'bool',
        'allow_pingbacks' => 'bool',
    ];

    public function terms()
    {
        return $this->belongsToMany(Term::class, 'post_term');
    }
    public function meta()
    {
        return $this->hasMany(PostMeta::class);
    }
    public function featured()
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }
    public function gallery()
    {
        return $this->belongsToMany(Media::class, 'post_media')->wherePivot('role', 'gallery')->withPivot('sort_order')->orderBy('post_media.sort_order');
    }
    public function revisions()
    {
        return $this->hasMany(PostRevision::class);
    }

    public function scopeType($q, string $type)
    {
        return $q->where('type', $type);
    }

    public function setSlugIfEmpty(): void
    {
        if (!$this->slug)
            $this->slug = \Str::slug($this->title) ?: \Str::random(8);
    }
}