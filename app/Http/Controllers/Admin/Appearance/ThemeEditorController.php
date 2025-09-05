<?php

namespace App\Http\Controllers\Admin\Appearance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ThemeEditorController extends Controller
{
    /* ------------------------------- Public ------------------------------- */

    /** Main page. */
    public function index()
    {
        [$slug, $root] = $this->activeThemeRootOrAbort(); // <- theme ROOT (not /views)
        return view('admin.appearance.editor.index', [
            'theme' => $slug,
            'root' => $root,
        ]);
    }

    /** JSON: full theme directory tree (folders first, then files). */
    public function tree(Request $request)
    {
        [, $root] = $this->activeThemeRootOrAbort();
        return response()->json([
            'ok' => true,
            'root' => $this->nice($root),
            'tree' => $this->buildTree($root, ''), // full tree from ROOT
        ]);
    }

    /** JSON: open a file and return contents. */
    public function open(Request $request)
    {
        [, $root] = $this->activeThemeRootOrAbort();

        $rel = trim((string) $request->query('path', ''), '/');
        $abs = $this->safeJoin($root, $rel);
        if (!$abs || !is_file($abs)) {
            return response()->json(['ok' => false, 'error' => 'not_found'], 404);
        }
        if (!$this->isEditable($abs)) {
            return response()->json(['ok' => false, 'error' => 'not_editable'], 422);
        }

        return response()->json([
            'ok' => true,
            'path' => $rel,
            'name' => basename($abs),
            'mime' => $this->mime($abs),
            'contents' => file_get_contents($abs),
        ]);
    }

    /** JSON: save a file. */
    public function save(Request $request)
    {
        $request->validate([
            'path' => ['required', 'string'],
            'content' => ['required', 'string'],
        ]);

        [, $root] = $this->activeThemeRootOrAbort();

        $rel = trim((string) $request->input('path'), '/');
        $abs = $this->safeJoin($root, $rel);
        if (!$abs || !is_file($abs)) {
            return response()->json(['ok' => false, 'error' => 'not_found'], 404);
        }
        if (!$this->isEditable($abs)) {
            return response()->json(['ok' => false, 'error' => 'not_editable'], 422);
        }

        file_put_contents($abs, (string) $request->input('content'));
        return response()->json(['ok' => true]);
    }

    /** JSON: super-light syntax checks for common Blade directive balance. */
    public function validateSyntax(Request $request)
    {
        $request->validate([
            'path' => ['required', 'string'],
            'content' => ['required', 'string'],
        ]);

        $content = (string) $request->input('content');
        $issues = [];
        foreach ([
            ['@if', '@endif'],
            ['@foreach', '@endforeach'],
            ['@for', '@endfor'],
            ['@isset', '@endisset'],
            ['@empty', '@endempty'],
            ['@auth', '@endauth'],
            ['@guest', '@endguest'],
            ['@can', '@endcan'],
            ['@switch', '@endswitch'],
            ['@section', '@endsection'],
            ['@push', '@endpush'],
        ] as [$open, $close]) {
            if (substr_count($content, $open) !== substr_count($content, $close)) {
                $issues[] = "Unbalanced directives: {$open} / {$close}";
            }
        }
        return response()->json(['ok' => empty($issues), 'issues' => $issues]);
    }

    /* ------------------------------ Helpers ------------------------------ */

    /** Resolve active theme slug and **theme root folder**. */
    protected function activeThemeRootOrAbort(): array
    {
        $slug = $this->activeThemeSlug();

        // Look for the THEME ROOT (not forcing /views)
        $candidates = [
            resource_path("themes/{$slug}"),
            base_path("themes/{$slug}"),
            base_path("Modules/Appearance/Themes/{$slug}"),
            resource_path("views/themes/{$slug}"),
        ];

        foreach ($candidates as $p) {
            if (is_dir($p)) {
                return [$slug, $p]; // <- return the root folder
            }
        }
        abort(404, 'Theme directory not found.');
    }

    /** Determine active slug: DB → file-fallback → config. */
    protected function activeThemeSlug(): string
    {
        $slug = (string) config('laracms.active_theme', 'laracms');
        try {
            $db = DB::table('themes')->where('status', 'active')->value('slug');
            if ($db)
                $slug = $db;
        } catch (\Throwable $e) {
        }

        $file = storage_path('app/appearance_active_theme.txt');
        if (is_file($file)) {
            $s = trim((string) @file_get_contents($file));
            if ($s !== '')
                $slug = $s;
        }
        return $slug;
    }

    /** Build a tree recursively from $root + $prefix. */
    protected function buildTree(string $root, string $prefix): array
    {
        $base = realpath($root);
        if (!$base)
            return [];

        $dir = $base . ($prefix ? DIRECTORY_SEPARATOR . $prefix : '');
        $items = @scandir($dir) ?: [];

        $ignore = ['.git', '.github', 'node_modules', 'vendor', '.idea', '.vscode'];
        $dirs = [];
        $files = [];

        foreach ($items as $entry) {
            if ($entry === '.' || $entry === '..')
                continue;

            $full = $dir . DIRECTORY_SEPARATOR . $entry;
            $rel = ltrim($prefix . '/' . $entry, '/');

            if (is_dir($full)) {
                if (in_array($entry, $ignore, true))
                    continue;
                $dirs[] = [
                    'type' => 'dir',
                    'name' => $entry,
                    'path' => $rel,
                    'children' => $this->buildTree($root, $rel),
                ];
            } else {
                if (!$this->isEditable($full))
                    continue;
                $files[] = [
                    'type' => 'file',
                    'name' => $entry,
                    'path' => $rel,
                ];
            }
        }

        usort($dirs, fn($a, $b) => strcmp($a['name'], $b['name']));
        usort($files, fn($a, $b) => strcmp($a['name'], $b['name']));
        return array_merge($dirs, $files);
    }

    /** Only allow editing typical theme text files. */
    protected function isEditable(string $abs): bool
    {
        $name = strtolower(basename($abs));
        if (Str::endsWith($name, '.blade.php'))
            return true;

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        return in_array($ext, [
            'php',
            'css',
            'scss',
            'sass',
            'less',
            'js',
            'json',
            'md',
            'svg',
            'xml',
            'htm',
            'html',
        ], true);
    }

    /** Join root + rel safely. */
    protected function safeJoin(string $root, string $rel): ?string
    {
        $rootReal = realpath($root);
        if (!$rootReal)
            return null;

        $candidate = $rootReal . DIRECTORY_SEPARATOR . str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $rel);
        $real = realpath($candidate);
        if (!$real)
            return null;

        return (str_starts_with($real, $rootReal . DIRECTORY_SEPARATOR) || $real === $rootReal) ? $real : null;
    }

    protected function mime(string $abs): string
    {
        $n = strtolower($abs);
        if (Str::endsWith($n, '.css'))
            return 'text/css';
        if (Str::endsWith($n, '.js'))
            return 'application/javascript';
        if (Str::endsWith($n, '.json'))
            return 'application/json';
        if (Str::endsWith($n, '.svg'))
            return 'image/svg+xml';
        if (Str::endsWith($n, '.xml'))
            return 'application/xml';
        return 'text/plain';
    }

    protected function nice(string $p): string
    {
        return str_replace('\\', '/', $p);
    }
}