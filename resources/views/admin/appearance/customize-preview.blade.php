<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Theme Preview</title>
    {!! $cssVarsHtml !!} {{-- <style>:root{...}</style> --}}
    <style>
        body {
            font-family: var(--font-body, Inter), system-ui, sans-serif;
            margin: 0;
        }

        h1,
        h2,
        h3 {
            font-family: var(--font-heading, Inter), system-ui, sans-serif;
        }

        .btn {
            padding: .6rem 1rem;
            border-radius: .6rem;
            border: 1px solid #e5e7eb;
            background: var(--theme-primary, #2563eb);
            color: #fff;
        }

        .accent {
            color: var(--theme-accent, #10b981);
        }

        header {
            padding: 16px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            gap: 12px;
            align-items: center;
        }

        header img {
            height: 40px;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 20px;
        }
    </style>
</head>

<body>
    <header>
        <img id="logo" src="" alt="" style="display:none">
        <strong id="title">Your Site</strong>
    </header>

    <div class="container">
        <h1>Preview Headline <span class="accent">Accent</span></h1>
        <p>This is a live preview canvas. Buttons, links, and text use your colors and fonts.</p>
        <button class="btn">Primary button</button>
    </div>

    <script>
        const liveBindings =
            @json($liveBindings); // {"#title:text":"general.site_title", "#logo:src":"general.logo"}

        const apply = (p) => {
            // CSS Vars inferred: --theme-primary, --theme-accent, --font-heading, --font-body
            const root = document.documentElement.style;
            if (p['colors.primary']) root.setProperty('--theme-primary', p['colors.primary']);
            if (p['colors.accent']) root.setProperty('--theme-accent', p['colors.accent']);
            if (p['typography.heading']) root.setProperty('--font-heading', p['typography.heading']);
            if (p['typography.body']) root.setProperty('--font-body', p['typography.body']);

            // Live bindings from schema
            for (const [selProp, key] of Object.entries(liveBindings)) {
                const [sel, prop] = selProp.split(':');
                const el = document.querySelector(sel);
                if (!el) continue;
                const val = p[key];
                if (prop === 'text') el.textContent = (val || 'Laracms');
                else if (prop === 'src') {
                    if (val) {
                        el.src = val;
                        el.style.display = 'block';
                    } else {
                        el.style.display = 'none';
                    }
                } else {
                    el.style[prop] = val || '';
                }
            }
        };

        window.addEventListener('message', (e) => {
            if (!e.data || e.data.type !== 'customize:update') return;
            apply(e.data.payload || {});
        });
    </script>
</body>

</html>
