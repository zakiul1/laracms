<?php

namespace App\Http\Controllers\Admin\Appearance;

use App\Http\Controllers\Controller;
use App\Support\Appearance\Customizer;
use App\Support\Appearance\ThemeManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class CustomizerController extends Controller
{
    public function __construct(
        protected ThemeManager $themes,
        protected Customizer $customizer
    ) {
    }

    public function index()
    {
        $slug = $this->themes->activeSlug();
        abort_if(!$slug, 400, 'No active theme');

        $schema = $this->customizer->schema($slug);
        $values = $this->customizer->values($slug);

        return view('admin.appearance.customize', compact('slug', 'schema', 'values'));
    }

    public function save(Request $r)
    {
        $slug = $this->themes->activeSlug();
        abort_if(!$slug, 400, 'No active theme');

        $schema = $this->customizer->schema($slug);
        $incoming = $this->collectIncoming($r, $schema);

        $this->customizer->save($slug, $incoming);

        return response()->json(['ok' => true]);
    }

    public function reset()
    {
        $slug = $this->themes->activeSlug();
        abort_if(!$slug, 400, 'No active theme');

        $this->customizer->reset($slug);

        return response()->json(['ok' => true]);
    }

    /**
     * Preview:
     * - By default redirects the iframe to the real front-end URL with ?__customize=1
     * - If ?theme=1 is passed, uses the theme-provided preview blade instead
     * - Optional ?path=/some/page to preview a specific route (e.g., blog post)
     */
    public function preview(Request $request)
    {
        $slug = $this->themes->activeSlug();
        abort_if(!$slug, 400, 'No active theme');

        // If explicitly asked, render the theme preview blade (design sandbox)
        if ($request->boolean('theme')) {
            app('view.finder')->flush();
            $candidates = [
                'theme::customize.preview',
                'theme::preview',
                "themes.$slug.customize.preview",
                "themes.$slug.preview",
            ];
            foreach ($candidates as $v) {
                if (View::exists($v)) {
                    return view($v, ['activeTheme' => $slug]);
                }
            }

            // Fallback generic preview scaffold
            $cssVars = $this->customizer->renderCssVars($slug);
            $liveBindings = $this->customizer->liveBindings($slug);
            return view('admin.appearance.customize-preview', [
                'slug' => $slug,
                'cssVarsHtml' => $cssVars,
                'liveBindings' => $liveBindings,
            ]);
        }

        // === Default: show ACTUAL site page inside iframe ===
        // Allow ?path=/page/to/preview (defaults to '/')
        $path = $request->filled('path') ? '/' . ltrim($request->input('path'), '/') : '/';

        // Keep it inside the app (strip host if pasted)
        $path = preg_replace('#^(https?:)?//[^/]+#', '', $path) ?: '/';

        // Avoid embedding admin itself
        if (str_starts_with($path, '/admin')) {
            $path = '/';
        }

        // Preserve any extra query (except path), and tag customize flag
        $query = $request->query();
        unset($query['path'], $query['theme']);
        $query['__customize'] = 1;

        $url = url($path) . (empty($query) ? '' : ('?' . http_build_query($query)));

        return redirect()->to($url);
    }

    /** Collect posted values according to schema (handles checkboxes). */
    protected function collectIncoming(Request $r, array $schema): array
    {
        $incoming = [];

        foreach (($schema['panels'] ?? []) as $panel) {
            foreach (($panel['fields'] ?? []) as $f) {
                $key = (string) ($f['key'] ?? '');
                if ($key === '')
                    continue;

                $type = (string) ($f['type'] ?? 'text');
                if ($type === 'checkbox') {
                    $incoming[$key] = (bool) $r->input($key, false);
                } else {
                    if ($r->has($key)) {
                        $incoming[$key] = $r->input($key);
                    }
                }
            }
        }

        return $incoming;
    }
}