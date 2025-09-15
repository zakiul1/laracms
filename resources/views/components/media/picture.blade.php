@props([
    'media' => null,
    'src' => null,
    'conversion' => null, // NEW: render a specific conversion if desired
    'sizes' => '100vw',
    'alt' => null,
    'class' => '',
    'loading' => 'lazy',
])

@php
    $altText = $alt ?? (is_object($media) ? $media->getCustomProperty('alt') ?? $media->name : '');
@endphp

@if ($media)
    @php
        $jpgSet = $webpSet = [];
        $widths = [320, 480, 640, 768, 1024, 1280, 1536, 1920];

        // 1) Try Spatie's native responsive srcset (original or a conversion)
$spatieSrcset = null;
if (method_exists($media, 'getSrcset')) {
    try {
        $tmp = $conversion ? $media->getSrcset($conversion) : $media->getSrcset();
        if (is_array($tmp)) {
            $spatieSrcset = implode(', ', $tmp);
        } elseif (is_string($tmp)) {
            $spatieSrcset = $tmp;
        }
    } catch (\Throwable $e) {
        $spatieSrcset = null;
    }
}

// 2) Build fallback srcsets from your conversions (w{width} / w{width}_webp)
foreach ($widths as $w) {
    $n = "w{$w}";
    $nw = "w{$w}_webp";
    if ($media->hasGeneratedConversion($n)) {
        $jpgSet[] = $media->getUrl($n) . " {$w}w";
    }
    if ($media->hasGeneratedConversion($nw)) {
        $webpSet[] = $media->getUrl($nw) . " {$w}w";
    }
}

// Fallback to md/lg/xl sets if nothing above exists
if (empty($jpgSet) && empty($webpSet)) {
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
// - If a specific conversion is requested, use that
// - Else original; if we have a JPEG srcset, pick its largest as default src
$defaultSrc = $conversion ? $media->getUrl($conversion) : $media->getUrl();
if (!$conversion && !empty($jpgSet)) {
    $last = end($jpgSet);
    $defaultSrc = strtok($last, ' ');
        }
    @endphp

    <picture>
        {{-- Prefer a WEBP source if you have conversion-based WEBP set --}}
        @if (!empty($webpSet))
            <source type="image/webp" srcset="{{ implode(', ', $webpSet) }}" sizes="{{ $sizes }}">
        @endif

        <img src="{{ $defaultSrc }}"
            @if ($spatieSrcset) {{-- Spatie-native responsive images (best) --}}
                srcset="{{ $spatieSrcset }}" sizes="{{ $sizes }}"
            @elseif (!empty($jpgSet))
                {{-- Fallback to your JPEG conversion-based srcset --}}
                srcset="{{ implode(', ', $jpgSet) }}" sizes="{{ $sizes }}" @endif
            alt="{{ e($altText) }}" loading="{{ $loading }}" decoding="async" class="{{ $class }}">
    </picture>
@elseif ($src)
    <img src="{{ $src }}" alt="{{ e($altText) }}" sizes="{{ $sizes }}" loading="{{ $loading }}"
        decoding="async" class="{{ $class }}">
@endif
