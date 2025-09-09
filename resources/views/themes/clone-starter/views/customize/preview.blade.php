<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Clone Starter — Theme Preview</title>
    @themeCssVars
    <style>
        body {
            font: 14px/1.5 system-ui, -apple-system, Segoe UI, Roboto, Inter, sans-serif;
            margin: 0;
            background: #f6f7fb
        }

        header {
            background: var(--theme-primary, #0ea5e9);
            color: #fff;
            padding: 12px 16px;
            font-weight: 600
        }

        .wrap {
            max-width: 1100px;
            margin: 20px auto;
            padding: 0 16px
        }

        .card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 22px
        }

        .btn {
            display: inline-block;
            padding: 10px 16px;
            border-radius: 10px;
            background: var(--theme-accent, #f97316);
            color: #fff
        }
    </style>
</head>

<body>
    <header id="site-title">{{ config('app.name', 'Laravel') }}</header>
    <div class="wrap">
        <div class="card">
            <h1>Clone Starter — Custom Preview</h1>
            <p>If you see this, your theme preview view is working.</p>
            <a class="btn" href="{{ url('/') }}" target="_blank">Open site home</a>
        </div>
    </div>
    <script>
        // live updates from the left panel
        window.addEventListener('message', e => {
            if (!e.data || e.data.type !== 'customize:update') return;
            const v = e.data.payload || {};
            if (v['colors.primary']) document.documentElement.style.setProperty('--theme-primary', v[
                'colors.primary']);
            if (v['colors.accent']) document.documentElement.style.setProperty('--theme-accent', v[
                'colors.accent']);
            if (typeof v['general.site_title'] === 'string') document.getElementById('site-title').textContent = v[
                'general.site_title'] || 'Laravel';
        });
    </script>
</body>

</html>
