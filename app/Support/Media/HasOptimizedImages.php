<?php

namespace App\Support\Media;

use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Image\Enums\Fit;

trait HasOptimizedImages
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',

            ]);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $widths = [320, 480, 640, 768, 1024, 1280, 1536, 1920];

        foreach ($widths as $w) {
            // JPEG width-based set
            $this->addMediaConversion("w{$w}")
                ->format('jpg')
                ->width($w)
                ->performOnCollections('images')
                ->nonQueued();

            // WebP counterpart
            $this->addMediaConversion("w{$w}_webp")
                ->format('webp')
                ->width($w)
                ->performOnCollections('images')
                ->nonQueued();
        }

        $this->addMediaConversion('thumb_webp')
            ->format('webp')
            ->fit(Fit::Crop, 300, 300)
            ->performOnCollections('images')
            ->nonQueued();
    }
}