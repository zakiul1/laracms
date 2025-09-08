<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>{{ $settings->site_name ?? config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Pull theme assets via helper from ThemeServiceProvider --}}
    <link rel="stylesheet" href="{{ $themeAsset('css/style.css') }}">
    <script defer src="{{ $themeAsset('js/app.js') }}"></script>
</head>

<body class="site">
    <header class="site-header">
        <div class="container">
            <a class="brand" href="{{ url('/') }}">
                {{ $settings->site_name ?? 'LaraCMS' }}
            </a>

            {{-- Active theme badge for clarity --}}
            <span class="badge">Theme: {{ $activeTheme }}</span>
        </div>

        @include('theme::partials.nav')
    </header>

    <main class="site-main container">
        @yield('content')
    </main>

    @include('theme::partials.footer')
</body>

</html>
