@props([
    'media' => null, // Spatie Media item (preferred)
    'src' => null, // Legacy fallback URL (used only if no $media)
    'conversion' => null, // Optional specific conversion to render (e.g., 'w1280')
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
if (method_exists($media, 'hasGeneratedConversion')) {
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

    // Fallback named aliases if the strict set isn't there
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
        }

        // 3) Choose default <img src> with strong preference for conversions (avoid original unless necessary)
        $defaultSrc = null;

        // If a specific conversion was requested and exists, use it.
        if (
            $conversion &&
            method_exists($media, 'hasGeneratedConversion') &&
            $media->hasGeneratedConversion($conversion)
        ) {
            $defaultSrc = $media->getUrl($conversion);
        }

        // Else prefer the largest JPEG conversion from the generated set
        if (!$defaultSrc && !empty($jpgSet)) {
            $last = end($jpgSet); // e.g. "/path/img-w1280.jpg 1280w"
            $defaultSrc = strtok($last, ' '); // strip the width descriptor
        }

        // Else try common large aliases
        if (!$defaultSrc && method_exists($media, 'hasGeneratedConversion')) {
            foreach (['xl', 'lg', 'md'] as $alias) {
                if ($media->hasGeneratedConversion($alias)) {
                    $defaultSrc = $media->getUrl($alias);
                    break;
                }
            }
        }

        // FINAL fallback: original file (may be AVIF). Only if absolutely nothing else exists.
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
    {{-- Legacy fallback (single-size). Note: we intentionally do NOT render AVIF here. --}}
    @php
        $pathLower = strtolower(parse_url($src, PHP_URL_PATH) ?? '');
    @endphp
    @if (!str_ends_with($pathLower, '.avif'))
        <img src="{{ $src }}" @if (!empty($sizes)) sizes="{{ $sizes }}" @endif
            alt="{{ e($altText) }}" loading="{{ $loading }}" decoding="async" class="{{ $class }}">
    @else
        {{-- Optional placeholder to avoid serving AVIF original --}}
        <div class="w-full aspect-[4/3] rounded-xl bg-gray-100 grid place-items-center text-gray-400">
            No image
        </div>
    @endif
@endif
