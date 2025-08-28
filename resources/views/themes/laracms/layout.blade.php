<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>{{ $settings->site_name ?? 'laracms' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php do_action('theme_head'); @endphp
    <style>
        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto
        }

        header,
        footer {
            background: #fff
        }

        a {
            text-decoration: none;
            color: #111827
        }
    </style>
</head>

<body class="min-h-screen" style="background:#f8fafc">
    <header class="p-4" style="box-shadow:0 1px 6px rgba(0,0,0,.06)">
        <div style="max-width:1024px;margin:0 auto;display:flex;align-items:center;justify-content:space-between">
            <a href="{{ route('home') }}" class="font-semibold">{{ $settings->site_name ?? 'laracms' }}</a>
            <nav>
                @if (isset($mainMenu) && $mainMenu)
                    @foreach ($mainMenu->items as $item)
                        <a href="{{ $item->url }}" style="margin-left:14px">{{ $item->label }}</a>
                    @endforeach
                @else
                    {!! apply_filters('theme_main_menu', '<a href="/">Home</a>') !!}
                @endif
            </nav>
        </div>
    </header>

    <main style="max-width:1024px;margin:0 auto;padding:24px">
        @yield('content')
    </main>

    <footer class="p-6" style="text-align:center;color:#6b7280">
        {{ data_get($settings->options, 'footer_text', '© ' . date('Y') . ' laracms') }}
        @php do_action('theme_footer'); @endphp
    </footer>
</body>

</html>
