<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8" />
    <title>{{ ($title ?? 'Admin') . ' — ' . config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        [x-cloak] {
            display: none !important
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- ✅ Make Alpine stores (toast, confirm, etc.) available globally, ASAP --}}
    <x-ui.alpine-stores />

    @php function_exists('do_action') && do_action('admin_head'); @endphp
    @stack('head')
</head>

<body class="h-full bg-white dark:bg-neutral-950 text-on-surface dark:text-on-surface-dark antialiased">
    @php
        /**
         * Prefer a composer-provided $adminMenu; else build it once here
         */
        $menuItems = $adminMenu ?? null;

        if ($menuItems === null) {
            /** @var \App\Support\Cms\AdminMenuRegistry $menu */
            $menu = app(\App\Support\Cms\AdminMenuRegistry::class);
            if (method_exists($menu, 'seedBaseline')) {
                $menu->seedBaseline();
            }
            function_exists('do_action') && do_action('admin_menu', $menu);
            $menuItems = $menu->list();
        }

        // -------- ACTIVE HELPERS (exact vs prefix) --------
        $normalizePath = function (?string $u): string {
            if (!$u) {
                return '';
            }
            $p = parse_url($u, PHP_URL_PATH) ?: '/';
            return '/' . ltrim(rtrim($p, '/'), '/'); // absolute path, no trailing slash
        };

        // submenu items: exact path match only
        $isUrlActiveExact = function (?string $url) use ($normalizePath): bool {
            return $normalizePath($url) === $normalizePath(request()->url());
        };

        // top-level links (no children): prefix match so /admin/media highlights on /admin/media/*
        $isUrlActivePrefix = function (?string $url) use ($normalizePath): bool {
            $cur = $normalizePath(request()->url());
            $base = $normalizePath($url);
            return $cur === $base || ($base !== '/' && str_starts_with($cur, $base . '/'));
        };

        // groups are active if any child is active (EXACT check)
        $groupIsActive = function (array $kids) use ($isUrlActiveExact): bool {
            foreach ($kids as $c) {
                if ($isUrlActiveExact($c['url'] ?? null)) {
                    return true;
                }
            }
            return false;
        };
    @endphp

    <div x-data="{ showSidebar: false }" class="relative flex w-full flex-col md:flex-row">
        <a class="sr-only" href="#main-content">skip to the main content</a>

        <!-- Mobile backdrop -->
        <div x-cloak x-show="showSidebar" class="fixed inset-0 z-10 bg-surface-dark/10 backdrop-blur-xs md:hidden"
            aria-hidden="true" @click="showSidebar=false" x-transition.opacity></div>

        <!-- SIDEBAR -->
        <nav class="fixed left-0 z-20 flex h-svh w-60 shrink-0 flex-col border-r border-outline bg-surface-alt p-4
               transition-transform duration-300 md:w-64 md:translate-x-0 md:relative
               dark:border-outline-dark dark:bg-surface-dark-alt"
            :class="showSidebar ? 'translate-x-0' : '-translate-x-60'" aria-label="sidebar navigation">

            <!-- Logo -->
            <a href="{{ route('admin.dashboard') }}"
                class="ml-2 w-fit text-2xl font-bold text-on-surface-strong dark:text-on-surface-dark-strong">
                <span class="sr-only">homepage</span>{{ config('app.name') }}
            </a>

            <!-- Search -->
            <div class="relative my-4 flex w-full max-w-xs flex-col gap-1 text-on-surface dark:text-on-surface-dark">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor" fill="none"
                    stroke-width="2"
                    class="absolute left-2 top-1/2 size-5 -translate-y-1/2 text-on-surface/50 dark:text-on-surface-dark/50"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input type="search"
                    class="w-full border border-outline rounded-radius bg-surface px-2 py-1.5 pl-9 text-sm
                              focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary
                              disabled:cursor-not-allowed disabled:opacity-75 dark:border-outline-dark
                              dark:bg-surface-dark/50 dark:focus-visible:outline-primary-dark"
                    name="search" aria-label="Search" placeholder="Search" />
            </div>

            <!-- MENU (dynamic via admin_menu hook) -->
            <div class="flex flex-col gap-2 overflow-y-auto pb-6">
                @foreach ($menuItems as $i => $item)
                    @php
                        $label = $item['label'] ?? 'Item';
                        $icon = $item['icon'] ?? null;
                        $url = $item['url'] ?? '#';
                        $kids = $item['children'] ?? [];
                        $hasKids = !empty($kids);
                        $isActive = $hasKids ? $groupIsActive($kids) : $isUrlActivePrefix($url);
                    @endphp

                    @if (!$hasKids)
                        <!-- Top-level link (icon allowed) -->
                        <a href="{{ $url }}"
                            class="flex items-center rounded-radius gap-2 px-2 py-1.5 text-sm font-medium underline-offset-2 focus-visible:underline focus:outline-hidden
                             {{ $isActive
                                 ? 'bg-primary/10 text-on-surface-strong dark:bg-primary-dark/10 dark:text-on-surface-dark-strong'
                                 : 'text-on-surface hover:bg-primary/5 hover:text-on-surface-strong dark:text-on-surface-dark dark:hover:bg-primary-dark/5 dark:hover:text-on-surface-dark-strong' }}">
                            @if ($icon)
                                <x-ui.icon :name="$icon" class="size-5 shrink-0" aria-hidden="true" />
                            @endif
                            <span>{{ $label }}</span>
                        </a>
                    @else
                        <!-- Collapsible group (submenu has NO icons) -->
                        <div x-data="{ isExpanded: {{ $isActive ? 'true' : 'false' }}, h: 0 }" x-init="$nextTick(() => { h = isExpanded ? ($refs.list?.scrollHeight || 0) : 0 })" class="flex flex-col">
                            <button type="button"
                                @click="isExpanded = !isExpanded; $nextTick(() => { h = isExpanded ? ($refs.list?.scrollHeight || 0) : 0 })"
                                id="grp-{{ $i }}-btn" aria-controls="grp-{{ $i }}"
                                :aria-expanded="isExpanded ? 'true' : 'false'"
                                class="flex items-center justify-between rounded-radius gap-2 px-2 py-1.5 text-sm font-medium underline-offset-2 focus:outline-hidden focus-visible:underline
                                           {{ $isActive
                                               ? 'bg-primary/10 text-on-surface-strong dark:bg-primary-dark/10 dark:text-on-surface-dark-strong'
                                               : 'text-on-surface hover:bg-primary/5 hover:text-on-surface-strong dark:text-on-surface-dark dark:hover:bg-primary-dark/5 dark:hover:text-on-surface-dark-strong' }}">
                                @if ($icon)
                                    <x-ui.icon :name="$icon" class="size-5 shrink-0" aria-hidden="true" />
                                @endif
                                <span class="mr-auto text-left">{{ $label }}</span>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                    class="size-5 transition-transform rotate-0 shrink-0"
                                    :class="isExpanded ? 'rotate-180' : 'rotate-0'" aria-hidden="true">
                                    <path fill-rule="evenodd"
                                        d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" />
                                </svg>
                            </button>

                            {{-- Animated collapse without @alpinejs/collapse --}}
                            <ul x-cloak x-ref="list" x-show="isExpanded" x-transition.opacity
                                :style="isExpanded
                                    ?
                                    'max-height:' + h + 'px; overflow:hidden; transition:max-height .3s ease;' :
                                    'max-height:0; overflow:hidden; transition:max-height .3s ease;'"
                                aria-labelledby="grp-{{ $i }}-btn" id="grp-{{ $i }}">
                                @foreach ($kids as $child)
                                    @php
                                        $cLabel = $child['label'] ?? 'Item';
                                        $cUrl = $child['url'] ?? '#';
                                        // Children are exact-match only
                                        $cActive = $isUrlActiveExact($cUrl);
                                    @endphp
                                    <li class="px-1 py-0.5 first:mt-2">
                                        <a href="{{ $cUrl }}"
                                            class="flex items-center rounded-radius gap-2 px-2 py-1.5 text-sm underline-offset-2 focus:outline-hidden focus-visible:underline
                                             {{ $cActive
                                                 ? 'bg-primary/10 text-on-surface-strong dark:bg-primary-dark/10 dark:text-on-surface-dark-strong'
                                                 : 'text-on-surface hover:bg-primary/5 hover:text-on-surface-strong dark:text-on-surface-dark dark:hover:bg-primary-dark/5 dark:hover:text-on-surface-dark-strong' }}">
                                            <span>{{ $cLabel }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endforeach
            </div>
        </nav>

        <!-- MAIN -->
        <div id="main-content" class="h-svh w-full overflow-y-auto p-4 bg-white dark:bg-neutral-950">
            @yield('content')
        </div>

        <!-- Mobile toggle -->
        <button
            class="fixed right-4 top-4 z-20 rounded-full bg-primary p-4 md:hidden text-on-primary dark:bg-primary-dark dark:text-on-primary-dark"
            @click="showSidebar = !showSidebar">
            <svg x-show="showSidebar" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor"
                class="size-5" aria-hidden="true">
                <path
                    d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8z" />
            </svg>
            <svg x-show="!showSidebar" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor"
                class="size-5" aria-hidden="true">
                <path
                    d="M0 3a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm5-1v12h9a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1zM4 2H2a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h2z" />
            </svg>
            <span class="sr-only">sidebar toggle</span>
        </button>
    </div>

    {{-- ✅ Global UI (visual parts at the end of body) --}}
    <x-ui.toasts />
    <x-ui.confirm-modal />
    <x-ui.flash-toasts />

    {{-- ✅ Your global media browser (modal/sheet) --}}
    <x-media-browser />

    @php function_exists('do_action') && do_action('admin_footer'); @endphp

    @stack('scripts')
    <script>
        // Start Alpine if not already started by app.js
        if (window.Alpine && !window.Alpine.__started) {
            try {
                window.Alpine.start();
                window.Alpine.__started = true;
            } catch (_) {}
        }
    </script>
</body>

</html>
