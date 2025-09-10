<?php

namespace App\Support\Media;

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait HasOptimizedImages
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/avif'
            ])
            ->useDisk(config('media-library.disk_name', 'public'))
            ->withResponsiveImages(); // keep if you like the tiny blur + auto widths
    }

    public function registerMediaConversions(Media $media = null): void
    {
        // If spatie/image isn't installed yet, just skip conversions gracefully
        if (!class_exists(\Spatie\Image\Manipulations::class)) {
            return;
        }

        $M = \Spatie\Image\Manipulations::class;

        // Choose the widths you want available for <img srcset>/<picture>
        $breakpoints = [320, 480, 768, 1024, 1280, 1536, 1920];

        foreach ($breakpoints as $w) {
            // JPEG fallback (good for older browsers / e-mail etc.)
            $this->addMediaConversion("w{$w}_jpg")
                ->fit($M::FIT_MAX, $w, $w)
                ->format($M::FORMAT_JPG)
                ->quality(82)
                ->performOnCollections('images');

            // WebP primary
            $this->addMediaConversion("w{$w}_webp")
                ->fit($M::FIT_MAX, $w, $w)
                ->format($M::FORMAT_WEBP)
                ->quality(80)
                ->performOnCollections('images');

            // AVIF (optional; only if your spatie/image supports it)
            if (\defined('\Spatie\Image\Manipulations::FORMAT_AVIF')) {
                $this->addMediaConversion("w{$w}_avif")
                    ->fit($M::FIT_MAX, $w, $w)
                    ->format($M::FORMAT_AVIF)
                    ->quality(50) // AVIF is efficient; lower quality is usually fine
                    ->performOnCollections('images');
            }
        }

        // Square admin/thumb for grids
        $this->addMediaConversion('thumb_webp')
            ->fit($M::FIT_CROP, 300, 300)
            ->format($M::FORMAT_WEBP)
            ->quality(80)
            ->performOnCollections('images');
    }
}