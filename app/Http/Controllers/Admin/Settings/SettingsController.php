<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Support\Settings\SettingsPageRegistry;
use App\Support\Settings\SettingsRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class SettingsController extends Controller
{
    public function __construct(
        protected SettingsPageRegistry $pages,
        protected SettingsRepository $repo
    ) {
    }

    /** Hub: redirect to the first tab (e.g. general). */
    public function index(): Response
    {
        $first = array_key_first($this->pages->all()) ?: 'general';

        if (app('router')->has("admin.settings.{$first}")) {
            return redirect()->route("admin.settings.{$first}");
        }
        return redirect()->route('admin.settings.page', ['page' => $first]);
    }

    /** Render a tab (General/Writing/Reading/…). */
    public function show(Request $request, ?string $page = null)
    {
        $page = $page ?: $request->route('page', 'general');
        if (!$this->pages->has($page)) {
            abort(404, "Unknown settings page '{$page}'.");
        }

        $meta = $this->pages->get($page);
        $values = array_replace_recursive(
            $this->pages->defaults($page),
            $this->repo->getPage($page)
        );

        return view('admin.settings.show', array_merge([
            'tabs' => $this->pages->all(),
            'pageKey' => $page,
            'page' => $meta,
            'values' => $values,
        ], $this->extras($page)));
    }

    /** Validate a tab without saving (AJAX-friendly). */
    public function validatePage(Request $request, ?string $page = null)
    {
        $page = $page ?: $request->route('page', 'general');
        if (!$this->pages->has($page))
            abort(404);

        $rules = $this->prefixedRules($page);             // reading.home_type, …
        $payload = [$page => $request->input($page, [])];   // ['reading' => [...]];

        $validated = validator($payload, $rules)->validate();

        return response()->json(['ok' => true, 'validated' => data_get($validated, $page, [])]);
    }

    /** Save a tab. */
    public function save(Request $request, ?string $page = null)
    {
        $page = $page ?: $request->route('page', 'general');
        if (!$this->pages->has($page))
            abort(404);

        $rules = $this->prefixedRules($page);
        $payload = [$page => $request->input($page, [])];

        $validated = validator($payload, $rules)->validate();
        $this->repo->setPage($page, data_get($validated, $page, []));

        return $request->wantsJson()
            ? response()->json(['ok' => true])
            : back()->with('success', 'Settings saved.');
    }

    /** Restore defaults for a tab. */
    public function restoreDefaults(Request $request, ?string $page = null)
    {
        $page = $page ?: $request->route('page', 'general');
        if (!$this->pages->has($page))
            abort(404);

        $this->repo->setPage($page, $this->pages->defaults($page));

        return $request->wantsJson()
            ? response()->json(['ok' => true, 'restored' => true])
            : back()->with('success', 'Defaults restored.');
    }

    /** Export all settings as JSON. */
    public function export()
    {
        return response()->json($this->repo->exportAll());
    }

    /** Import settings JSON. */
    public function import(Request $request)
    {
        $data = $request->validate(['json' => 'required']);
        $decoded = json_decode($data['json'], true);

        if (!is_array($decoded)) {
            return back()->with('error', 'Invalid JSON payload.');
        }

        $this->repo->importAll($decoded);
        return back()->with('success', 'Settings imported.');
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Convert the registry rules for a page into dot-notation rules
     * acceptable by Laravel's validator (e.g. "reading.home_type").
     */
    private function prefixedRules(string $page): array
    {
        $prefixed = [];
        foreach ($this->pages->rules($page) as $field => $rule) {
            $prefixed["{$page}.{$field}"] = $rule;
        }
        return $prefixed;
    }

    /**
     * Extra lookups used by various tabs (lists, enums, etc.). Safe on fresh installs.
     */
    private function extras(string $page): array
    {
        // Timezones, weekdays
        $timezones = \DateTimeZone::listIdentifiers();
        $weekdays = [0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];

        // Locales
        $cfgLocales = config('app.supported_locales');
        if (is_array($cfgLocales) && $cfgLocales) {
            $locales = [];
            foreach ($cfgLocales as $k => $v) {
                $locales[is_int($k) ? $v : $k] = is_int($k) ? strtoupper($v) : $v;
            }
        } else {
            $cur = config('app.locale', 'en');
            $locales = [$cur => strtoupper($cur)];
        }

        // Roles
        $roles = config('auth.roles', [
            'subscriber' => 'Subscriber',
            'author' => 'Author',
            'editor' => 'Editor',
            'admin' => 'Administrator',
        ]);

        // Pages & categories (best-effort; guarded)
        $pages = [];
        $categories = [];

        try {
            if (Schema::hasTable('posts')) {
                // Resolve the title column flexibly.
                $titleCol = Schema::hasColumn('posts', 'title')
                    ? 'title'
                    : (Schema::hasColumn('posts', 'name') ? 'name' : null);

                $q = DB::table('posts');

                // Select id + title alias
                if ($titleCol) {
                    $q->select(['id', DB::raw("$titleCol as title")]);
                } else {
                    $q->select(['id', DB::raw("CONCAT('Page #', id) as title")]);
                }

                // Filter to pages if such a column exists
                if (Schema::hasColumn('posts', 'type')) {
                    $q->where('type', 'page');
                } elseif (Schema::hasColumn('posts', 'post_type')) {
                    $q->where('post_type', 'page');
                }

                // Be tolerant with statuses
                if (Schema::hasColumn('posts', 'status')) {
                    $q->whereIn('status', ['publish', 'published', 'public', 'active', 1, '1']);
                }

                // Order and pluck
                $orderCol = $titleCol ?: 'id';
                $pages = $q->orderBy($orderCol)->pluck('title', 'id')->all();
            }

            if (Schema::hasTable('terms')) {
                $categories = DB::table('terms')
                    ->when(Schema::hasColumn('terms', 'taxonomy'), fn($q) => $q->where('taxonomy', 'category'))
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all();
            } elseif (Schema::hasTable('categories')) {
                $categories = DB::table('categories')->orderBy('name')->pluck('name', 'id')->all();
            }
        } catch (\Throwable $e) {
            // fresh DBs are fine; lists can be empty
        }

        // For blades that expect arrays: ['id'=>..,'title'=>..]
        $pagesForSelect = collect($pages)
            ->map(fn($title, $id) => ['id' => $id, 'title' => $title])
            ->values()
            ->all();

        $out = compact('timezones', 'weekdays', 'locales', 'roles', 'pages', 'categories', 'pagesForSelect');

        // Extra data for the Permalinks tab (examples/labels)
        if ($page === 'permalinks') {
            $today = now();
            $out['labels'] = [
                'plain' => 'Plain',
                'dayname' => 'Day and Name',
                'monthname' => 'Month and Name',
                'numeric' => 'Numeric',
                'postname' => 'Post name',
                'custom' => 'Custom Structure',
            ];
            $out['choices'] = [
                'plain' => url('/?p=123'),
                'dayname' => url(sprintf('/%s/%s/%s/sample-post/', $today->format('Y'), $today->format('m'), $today->format('d'))),
                'monthname' => url(sprintf('/%s/%s/sample-post/', $today->format('Y'), $today->format('m'))),
                'numeric' => url('/archives/123'),
                'postname' => url('/sample-post/'),
                'custom' => '',
            ];
        }

        return $out;
    }
}