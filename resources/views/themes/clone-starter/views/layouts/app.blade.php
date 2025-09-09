<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title', config('app.name'))</title>

    {{-- Inject saved variables from Customize --}}
    @themeCssVars

    {{-- your base styles ... --}}
    <style>
        /* ... existing CSS ... */
    </style>
</head>

<body>
    @include('theme::partials.header')

    @hasSection('hero')
        <section class="hero">
            <div class="container">@yield('hero')</div>
        </section>
    @endif

    <main>
        <div class="container">@yield('content')</div>
    </main>

    @include('theme::partials.footer')

    {{-- Live preview only when in customize mode --}}
    @if (request()->boolean('__customize'))
        @php
            // Optional: use your schema’s live bindings (selector:prop => key), if defined
            $__live = [];
            try {
                $__live = app(\App\Support\Appearance\Customizer::class)->liveBindings($activeTheme ?? '');
            } catch (\Throwable $e) {
            }
        @endphp
        <script>
            (function() {
                const map = @json($__live);

                function setCssVar(name, val) {
                    if (typeof val === 'string' && val !== '') {
                        document.documentElement.style.setProperty(name, val);
                    }
                }

                function apply(vars) {
                    // Example: colors → CSS variables
                    if (vars['colors.primary']) setCssVar('--theme-primary', vars['colors.primary']);
                    if (vars['colors.accent']) setCssVar('--theme-accent', vars['colors.accent']);

                    // Apply live bindings from schema:  "#site-title:text" => "general.site_title"
                    for (const key in map) {
                        const [selProp, targetKey] = [key, map[key]];
                        const idx = selProp.lastIndexOf(':');
                        const sel = selProp.substring(0, idx);
                        const prop = selProp.substring(idx + 1); // 'text' or an attribute like 'src', 'href'
                        const val = vars[targetKey];
                        if (val == null) continue;

                        document.querySelectorAll(sel).forEach(el => {
                            if (prop === 'text') el.textContent = val;
                            else el.setAttribute(prop, val);
                        });
                    }
                }

                window.addEventListener('message', (e) => {
                    if (!e.data || e.data.type !== 'customize:update') return;
                    apply(e.data.payload || {});
                });
            })();
        </script>
    @endif
</body>

</html>
