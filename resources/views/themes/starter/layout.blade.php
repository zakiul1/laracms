<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <title>
        @hasSection('title')
            @yield('title') — {{ $settings->site_title ?? config('app.name') }}
        @else
            {{ $settings->site_title ?? config('app.name') }}
        @endif
    </title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Basic SEO fallbacks; override per-view if you pass $seo --}}
    @php
        $metaTitle = isset($seo['meta_title']) ? $seo['meta_title'] : trim($__env->yieldContent('title'));
        $metaDesc = $seo['meta_description'] ?? ($settings->site_tagline ?? '');
    @endphp
    @if (!empty($metaTitle))
        <meta name="title" content="{{ $metaTitle }}">
    @endif
    @if (!empty($metaDesc))
        <meta name="description" content="{{ $metaDesc }}">
    @endif

    {{-- OpenGraph/Twitter fallbacks --}}
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $seo['og_title'] ?? $metaTitle }}">
    <meta property="og:description" content="{{ $seo['og_description'] ?? $metaDesc }}">
    @if (!empty($seo['og_image_url']))
        <meta property="og:image" content="{{ $seo['og_image_url'] }}">
    @endif

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seo['twitter_title'] ?? $metaTitle }}">
    <meta name="twitter:description" content="{{ $seo['twitter_description'] ?? $metaDesc }}">
    @if (!empty($seo['twitter_image_url']))
        <meta name="twitter:image" content="{{ $seo['twitter_image_url'] }}">
    @endif

    {{-- Your app.css (Tailwind) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-white text-neutral-900 antialiased">

    @includeIf("themes.$activeTheme.partials.nav")

    <main class="min-h-screen">
        @yield('content')
    </main>

    @includeIf("themes.$activeTheme.partials.footer")

</body>

</html>
