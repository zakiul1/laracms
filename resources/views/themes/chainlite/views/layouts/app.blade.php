@php
    // SEO hydration
    $seo = $seo ?? [];
    $site = setting('general.site_title', config('app.name'));
    $tagline = setting('general.tagline', '');
    $title = $seo['meta_title'] ?? trim($pageTitle ?? '' ?: $title ?? '');
    $title = $title !== '' ? $title : ($tagline ? "$site — $tagline" : $site);
    $desc = $seo['meta_description'] ?? setting('seo.meta_description', setting('general.site_description', ''));
    $keywords = $seo['meta_keywords'] ?? setting('seo.meta_keywords', '');
    $ogImage = $seo['og_image'] ?? setting('seo.og_image', null);
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>{{ $title }}</title>

    @if ($desc)
        <meta name="description" content="{{ $desc }}">
    @endif
    @if ($keywords)
        <meta name="keywords" content="{{ $keywords }}">
    @endif

    <meta property="og:title" content="{{ $title }}">
    <meta property="og:site_name" content="{{ $site }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    @if ($desc)
        <meta property="og:description" content="{{ $desc }}">
    @endif
    @if ($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
    @endif
    <meta name="robots"
        content="{{ $seo['robots_index'] ?? true ? 'index' : 'noindex' }}, {{ $seo['robots_follow'] ?? true ? 'follow' : 'nofollow' }}">

    {{-- WordPress-like: print enqueued styles/scripts for <head> --}}
    @vite(['resources/views/themes/chainlite/assets/theme.js', 'resources/views/themes/chainlite/assets/dist/theme.css'])

    {!! theme_head() !!}
    @stack('head')
</head>

<body class="bg-white text-slate-800">
    @include('theme::partials.header')

    <main id="content" class="min-h-[60vh]">

        @yield('content')
    </main>

    @include('theme::partials.footer')

    {{-- Footer scripts --}}
    {!! theme_footer() !!}
    @stack('scripts')
</body>

</html>
