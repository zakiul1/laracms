<?php

namespace App\Http\Controllers\Admin\Appearance;

use App\Http\Controllers\Controller;
use App\Support\Appearance\ThemeManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomizerController extends Controller
{
    private const TABLE = 'appearance_settings';
    private const BASE_KEY = 'customizer';

    public function __construct(private ThemeManager $themes)
    {
    }

    public function index()
    {
        $slug = (string) $this->themes->activeSlug();
        $title = $this->themeTitle($slug);

        $schema = $this->loadSchema($slug);     // theme-defined schema or []
        $defaults = $this->defaultsFromSchema($schema);
        $current = $this->getSettings($slug);

        // Merge defaults <- saved
        $settings = array_replace_recursive($defaults, $current);

        return view('admin.appearance.customize.index', [
            'schema' => $schema,
            'settings' => $settings,
            'activeThemeSlug' => $slug,
            'activeThemeTitle' => $title,
            'previewUrl' => route('admin.appearance.customize.preview'),
        ]);
    }

    public function save(Request $request)
    {
        $slug = (string) $this->themes->activeSlug();
        $schema = $this->loadSchema($slug);

        // Build rules: { field => rules }
        $rules = $this->rulesFromSchema($schema);

        // Expect flat 'settings[field]' keys
        $payload = $request->input('settings', []);
        // Validate against flat rules
        $validated = validator($payload, $rules)->validate();

        $this->setSettings($slug, $validated);

        return $request->wantsJson()
            ? response()->json(['ok' => true])
            : back()->with('success', 'Customizer settings saved.');
    }

    public function preview()
    {
        $slug = (string) $this->themes->activeSlug();
        $title = $this->themeTitle($slug);
        $schema = $this->loadSchema($slug);
        $defaults = $this->defaultsFromSchema($schema);
        $current = $this->getSettings($slug);
        $customizer = array_replace_recursive($defaults, $current);

        // If the theme provides a Blade preview, prefer it: resources/views/themes/<slug>/customizer-preview.blade.php
        // which is namespaced as "theme::customizer-preview" by your ThemeManager.
        if (view()->exists('theme::customizer-preview')) {
            // Make sure ThemeManager bound the "theme::" namespace earlier in your AppServiceProvider.
            return response()->view('theme::customizer-preview', [
                'customizer' => $customizer,
                'activeThemeSlug' => $slug,
                'activeThemeTitle' => $title,
            ]);
        }

        // Fallback minimal HTML (uses common keys if present)
        $siteTitle = $customizer['site_title'] ?? config('app.name', 'LaraCMS');
        $primary = $customizer['primary'] ?? $customizer['colors']['primary'] ?? '#0ea5e9';
        $accent = $customizer['accent'] ?? $customizer['colors']['accent'] ?? '#f97316';
        $font = $customizer['typography']['font'] ?? 'system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif';
        $customCss = $customizer['custom_css'] ?? '';

        $html = <<<HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Preview — {$this->e($siteTitle)}</title>
<style>
:root{ --primary: {$this->e($primary)}; --accent: {$this->e($accent)}; --radius: 12px; }
html,body{margin:0;padding:0;height:100%}
body{font-family: {$font}; background:#fafafa; color:#0f172a}
.header{padding:14px 18px; background:var(--primary); color:#fff; font-weight:600}
.wrap{padding:22px}
.card{background:#fff;border:1px solid #e5e7eb;border-radius:var(--radius);padding:18px;box-shadow:0 1px 2px rgba(0,0,0,.04)}
.button{display:inline-block;background:var(--accent);color:#fff;border:none;padding:8px 14px;border-radius:10px;text-decoration:none}
a{color:var(--primary)}
{$customCss}
</style>
</head>
<body>
  <div class="header">{$this->e($siteTitle)} — {$this->e($title)} ({$this->e($slug)})</div>
  <div class="wrap">
    <div class="card">
      <h2 style="margin-top:0">Theme Preview</h2>
      <p>This is the generic preview because your theme did not provide a custom preview view.</p>
      <ul>
        <li><strong>Primary:</strong> {$this->e($primary)}</li>
        <li><strong>Accent:</strong> {$this->e($accent)}</li>
      </ul>
      <p><a class="button" href="{$this->e(route('home'))}" target="_blank" rel="noopener">Open site home</a></p>
    </div>
  </div>
  <script>
    // Optional: live-update via postMessage (matches the Blade below)
    window.addEventListener('message', (e) => {
      if (!e.data || e.data.type !== 'customizer:update') return;
      const s = e.data.payload || {};
      if (s.primary)  document.documentElement.style.setProperty('--primary', s.primary);
      if (s.accent)   document.documentElement.style.setProperty('--accent', s.accent);
      if (s.site_title) document.querySelector('.header').textContent = s.site_title + ' — ' + {$this->json($title)} + ' ({$this->json($slug)})';
    });
  </script>
</body>
</html>
HTML;

        return response($html);
    }

    // ---------------- schema / storage utils ----------------

    private function loadSchema(string $slug): array
    {
        // You can also ask ThemeManager for an absolute path if it exposes it.
        // Fallback to config path:
        $base = config('appearance.themes_path', resource_path('themes'));
        $path = rtrim($base, '/\\') . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . 'customizer.php';
        if ($slug !== '' && is_file($path)) {
            $schema = include $path;
            return is_array($schema) ? $schema : [];
        }
        return []; // no schema -> generic fields
    }

    private function defaultsFromSchema(array $schema): array
    {
        $out = [];
        foreach (($schema['sections'] ?? []) as $sec) {
            foreach (($sec['fields'] ?? []) as $key => $def) {
                if (array_key_exists('default', $def)) {
                    $out[$key] = $def['default'];
                }
            }
        }
        return $out;
    }

    private function rulesFromSchema(array $schema): array
    {
        $rules = [];
        foreach (($schema['sections'] ?? []) as $sec) {
            foreach (($sec['fields'] ?? []) as $key => $def) {
                if (!empty($def['rules'])) {
                    $rules[$key] = $def['rules'];
                } else {
                    // sensible defaults by type
                    $t = $def['type'] ?? 'text';
                    $rules[$key] = match ($t) {
                        'checkbox' => 'boolean',
                        'color' => 'nullable|string|max:20',
                        'select' => !empty($def['choices']) ? 'nullable|in:' . implode(',', array_keys($def['choices'])) : 'nullable|string',
                        'image' => 'nullable|url|max:500',
                        'number' => 'nullable|numeric',
                        default => 'nullable|string',
                    };
                }
            }
        }
        return $rules;
    }

    private function settingsKey(string $slug): string
    {
        return $slug !== '' ? self::BASE_KEY . ':' . $slug : self::BASE_KEY;
    }

    private function getSettings(string $slug): array
    {
        if (!Schema::hasTable(self::TABLE))
            return [];
        $row = DB::table(self::TABLE)->where('key', $this->settingsKey($slug))->first();
        if (!$row)
            return [];
        $decoded = json_decode($row->value ?? '[]', true);
        return is_array($decoded) ? $decoded : [];
    }

    private function setSettings(string $slug, array $settings): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            Schema::create(self::TABLE, function ($table) {
                /** @var \Illuminate\Database\Schema\Blueprint $table */
                $table->id();
                $table->string('key')->unique();
                $table->longText('value')->nullable();
                $table->timestamps();
            });
        }

        $key = $this->settingsKey($slug);
        $payload = [
            'value' => json_encode($settings, JSON_UNESCAPED_SLASHES),
            'updated_at' => now(),
        ];

        $exists = DB::table(self::TABLE)->where('key', $key)->exists();

        if ($exists) {
            DB::table(self::TABLE)->where('key', $key)->update($payload);
        } else {
            DB::table(self::TABLE)->insert(array_merge([
                'key' => $key,
                'created_at' => now(),
            ], $payload));
        }
    }

    private function themeTitle(string $slug): string
    {
        if ($slug === '')
            return 'No active theme';
        if (method_exists($this->themes, 'activeTitle')) {
            $t = (string) $this->themes->activeTitle();
            if ($t !== '')
                return $t;
        }
        return ucwords(str_replace(['-', '_'], ' ', $slug));
    }

    private function e(string $v): string
    {
        return htmlspecialchars($v, ENT_QUOTES, 'UTF-8', false);
    }
    private function json($v): string
    {
        return json_encode($v ?? '');
    }
}