@props([
    'media', // Spatie Media instance
    'class' => '',
    'sizes' => '100vw', // override per layout
    'alt' => null,
    'loading' => 'lazy',
])

@if ($media)
    @php
        $breakpoints = [320, 480, 768, 1024, 1280, 1536, 1920];

        $webpSet = [];
        $jpgSet = [];

        foreach ($breakpoints as $w) {
            $webpSet[] = $media->getUrl("w{$w}_webp") . " {$w}w";
            $jpgSet[] = $media->getUrl("w{$w}") . " {$w}w";
        }

        // pick a safe default src
        $defaultSrc = $media->hasGeneratedConversion('w768') ? $media->getUrl('w768') : $media->getUrl();

        $altText = $alt ?? ($media->getCustomProperty('alt') ?? $media->name);
    @endphp

    <picture>
        <source type="image/webp" srcset="{{ implode(', ', $webpSet) }}" sizes="{{ $sizes }}">
        <img src="{{ $defaultSrc }}" srcset="{{ implode(', ', $jpgSet) }}" sizes="{{ $sizes }}"
            alt="{{ e($altText) }}" loading="{{ $loading }}" decoding="async" class="{{ $class }}">
    </picture>
@endif
