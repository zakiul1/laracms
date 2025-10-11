<?php

namespace App\Support;

use App\Models\Post;
use App\Models\Media as LegacyMedia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SyncFeatured
{
    /**
     * If a post has a legacy featured_media_id, try to attach that file
     * to Spatie's "featured" collection (skip AVIF so conversions succeed).
     */
    public static function run(Post $post): void
    {
        if (!$post->featured_media_id)
            return;

        $legacy = LegacyMedia::find($post->featured_media_id);
        if (!$legacy)
            return;

        $rel = $legacy->path
            ?? $legacy->file_path
            ?? $legacy->filepath
            ?? (isset($legacy->dir, $legacy->filename) ? trim($legacy->dir, '/') . '/' . $legacy->filename : null);

        if (!$rel)
            return;

        $rel = ltrim($rel, '/');
        $disk = $legacy->disk ?: 'public';

        $cand = Storage::disk($disk)->exists($rel)
            ? Storage::disk($disk)->path($rel)
            : (Storage::disk('public')->exists($rel) ? Storage::disk('public')->path($rel) : null);

        if (!$cand)
            return;
        if (Str::endsWith(strtolower($cand), '.avif'))
            return; // skip AVIF source

        // Optional: clear previous featured
        // $post->clearMediaCollection('featured');

        $post->addMedia($cand)
            ->preservingOriginal()
            ->toMediaCollection('featured'); // your conversions are nonQueued(), so done immediately
    }
}