@props([
    'media' => null,
    'src' => null,
    'conversion' => null, // optional: render a specific conversion
    'sizes' => '100vw',
    'alt' => null,
    'class' => '',
    'loading' => 'lazy',
])

@php
    $altText = $alt ?? (is_object($media) ? $media->getCustomProperty('alt') ?? $media->name : '');

    /**
     * Filter a srcset string/array and remove entries that match a substring (e.g. '.avif').
     */
    $filterSrcset = function ($srcset, string $ban) {
        if (empty($srcset)) {
            return null;
        }

        $parts = is_array($srcset) ? $srcset : explode(',', $srcset);
        $clean = [];
        foreach ($parts as $p) {
            $item = is_array($srcset) ? $p : trim($p);
            if ($item !== '' && stripos($item, $ban) === false) {
                $clean[] = $item;
            }
        }
        return empty($clean) ? null : (is_array($srcset) ? $clean : implode(', ', $clean));
    };
@endphp

@if ($media)
    @php
        $jpgSet = $webpSet = [];
        $widths = [320, 480, 640, 768, 1024, 1280, 1536, 1920];

        // 1) Try Spatie's native responsive srcset (for original or a conversion)
$spatieSrcset = null;
if (method_exists($media, 'getSrcset')) {
    try {
        $tmp = $conversion ? $media->getSrcset($conversion) : $media->getSrcset();
        // Normalize to string
        if (is_array($tmp)) {
            $tmp = implode(', ', $tmp);
        }
        // Remove any accidental AVIF entries defensively
        $spatieSrcset = $filterSrcset($tmp, '.avif');
    } catch (\Throwable $e) {
        $spatieSrcset = null;
    }
}

// 2) Build fallback srcsets from your named conversions (w{width} / w{width}_webp)
foreach ($widths as $w) {
    $n = "w{$w}";
    $nw = "w{$w}_webp";
    if (method_exists($media, 'hasGeneratedConversion')) {
        if ($media->hasGeneratedConversion($n)) {
            $jpgSet[] = $media->getUrl($n) . " {$w}w";
        }
        if ($media->hasGeneratedConversion($nw)) {
            $webpSet[] = $media->getUrl($nw) . " {$w}w";
        }
    }
}

// Fallback to md/lg/xl sets if nothing above exists
if (empty($jpgSet) && empty($webpSet) && method_exists($media, 'hasGeneratedConversion')) {
    foreach (['md' => 640, 'lg' => 960, 'xl' => 1200] as $name => $w) {
        if ($media->hasGeneratedConversion($name)) {
            $jpgSet[] = $media->getUrl($name) . " {$w}w";
        }
        if ($media->hasGeneratedConversion("{$name}-webp")) {
            $webpSet[] = $media->getUrl("{$name}-webp") . " {$w}w";
        }
    }
}

// 3) Choose default <img src>
//    - If a specific conversion is requested, use that
//    - Else prefer the largest JPEG conversion
//    - Else try a common large JPG alias like 'xl'
//    - Else fall back to original
$defaultSrc = $conversion ? $media->getUrl($conversion) : null;

if (!$defaultSrc && !empty($jpgSet)) {
    $last = end($jpgSet); // e.g. "/path/img-xl.jpg 1200w"
    $defaultSrc = strtok($last, ' '); // strip the width descriptor
}

if (!$defaultSrc && method_exists($media, 'hasGeneratedConversion')) {
    if ($media->hasGeneratedConversion('xl')) {
        $defaultSrc = $media->getUrl('xl');
    } elseif ($media->hasGeneratedConversion('lg')) {
        $defaultSrc = $media->getUrl('lg');
            }
        }

        if (!$defaultSrc) {
            $defaultSrc = $media->getUrl();
        }
    @endphp

    <picture>
        {{-- Prefer a WebP source if available --}}
        @if (!empty($webpSet))
            <source type="image/webp" srcset="{{ implode(', ', $webpSet) }}" sizes="{{ $sizes }}">
        @endif

        <img src="{{ $defaultSrc }}"
            @if ($spatieSrcset) srcset="{{ $spatieSrcset }}" sizes="{{ $sizes }}"
            @elseif (!empty($jpgSet))
                srcset="{{ implode(', ', $jpgSet) }}" sizes="{{ $sizes }}" @endif
            alt="{{ e($altText) }}" loading="{{ $loading }}" decoding="async" class="{{ $class }}">
    </picture>
@elseif ($src)
    <img src="{{ $src }}" alt="{{ e($altText) }}"
        @if (!empty($sizes)) sizes="{{ $sizes }}" @endif loading="{{ $loading }}"
        decoding="async" class="{{ $class }}">
@endif
