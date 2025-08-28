<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8" />
    <title>{{ ($title ?? 'Admin') . ' — ' . config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php function_exists('do_action') && do_action('admin_head'); @endphp
</head>

<body class="h-full bg-surface dark:bg-surface-dark text-on-surface dark:text-on-surface-dark antialiased">

    {{-- PAGE SHELL WITH YOUR PENGUIN UI SIDEBAR --}}
    <div x-data="{ sidebarIsOpen: false }" class="relative flex w-full flex-col md:flex-row">
        <a class="sr-only" href="#main-content">skip to the main content</a>

        <!-- Mobile overlay -->
        <div x-cloak x-show="sidebarIsOpen" class="fixed inset-0 z-20 bg-surface-dark/10 backdrop-blur-xs md:hidden"
            aria-hidden="true" x-on:click="sidebarIsOpen = false" x-transition.opacity>
        </div>

        <!-- SIDEBAR (your markup, unchanged except one link) -->
        <nav x-cloak
            class="fixed left-0 z-30 flex h-svh w-60 shrink-0 flex-col border-r border-outline bg-surface-alt p-4 transition-transform duration-300 md:w-64 md:translate-x-0 md:relative dark:border-outline-dark dark:bg-surface-dark-alt"
            x-bind:class="sidebarIsOpen ? 'translate-x-0' : '-translate-x-60'" aria-label="sidebar navigation">

            {{-- logo --}}
            <a href="{{ route('admin.dashboard') }}"
                class="ml-2 w-fit text-2xl font-bold text-on-surface-strong dark:text-on-surface-dark-strong">
                <span class="sr-only">homepage</span>
                {{-- … your SVG logo kept as-is … --}}
            </a>

            {{-- search --}}
            <div class="relative my-4 flex w-full max-w-xs flex-col gap-1 text-on-surface dark:text-on-surface-dark">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor" fill="none"
                    stroke-width="2"
                    class="absolute left-2 top-1/2 size-5 -translate-y-1/2 text-on-surface/50 dark:text-on-surface-dark/50"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input type="search"
                    class="w-full border border-outline rounded-radius bg-surface px-2 py-1.5 pl-9 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:cursor-not-allowed disabled:opacity-75 dark:border-outline-dark dark:bg-surface-dark/50 dark:focus-visible:outline-primary-dark"
                    name="search" aria-label="Search" placeholder="Search" />
            </div>

            {{-- LINKS (first “Dashboard” points to admin.dashboard) --}}
            <div class="flex flex-col gap-2 overflow-y-auto pb-6">
                <a href="{{ route('admin.dashboard') }}"
                    class="flex items-center rounded-radius gap-2 px-2 py-1.5 text-sm font-medium underline-offset-2
                  {{ request()->routeIs('admin.dashboard') ? 'bg-primary/10 text-on-surface-strong dark:bg-primary-dark/10 dark:text-on-surface-dark-strong' : 'text-on-surface hover:bg-primary/5 hover:text-on-surface-strong dark:text-on-surface-dark dark:hover:bg-primary-dark/5 dark:hover:text-on-surface-dark-strong' }}">
                    {{-- icon --}}
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                        class="size-5 shrink-0" aria-hidden="true">
                        <path
                            d="M15.5 2A1.5 1.5 0 0 0 14 3.5v13a1.5 1.5 0 0 0 1.5 1.5h1a1.5 1.5 0 0 0 1.5-1.5v-13A1.5 1.5 0 0 0 16.5 2h-1ZM9.5 6A1.5 1.5 0 0 0 8 7.5v9A1.5 1.5 0 0 0 9.5 18h1a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 10.5 6h-1ZM3.5 10A1.5 1.5 0 0 0 2 11.5v5A1.5 1.5 0 0 0 3.5 18h1A1.5 1.5 0 0 0 6 16.5v-5A1.5 1.5 0 0 0 4.5 10h-1Z" />
                    </svg>
                    <span>Dashboard</span>
                </a>

                {{-- keep the rest of your links exactly as you posted --}}
                {{-- Marketing (active example) --}}
                <a href="#"
                    class="flex items-center rounded-radius gap-2 bg-primary/10 px-2 py-1.5 text-sm font-medium text-on-surface-strong dark:bg-primary-dark/10 dark:text-on-surface-dark-strong">
                    … Marketing …
                </a>

                {{-- Sales / Performance / Referrals / Licenses / Settings --}}
                {{-- (all the remaining anchors from your snippet unchanged) --}}
                {!! '' !!}
            </div>
        </nav>

        <!-- TOP BAR + MAIN -->
        <div class="h-svh w-full overflow-y-auto bg-surface dark:bg-surface-dark">
            <nav class="sticky top-0 z-10 flex items-center justify-between border-b border-outline bg-surface-alt px-4 py-2 dark:border-outline-dark dark:bg-surface-dark-alt"
                aria-label="top navigation bar">
                <!-- mobile sidebar open -->
                <button type="button" class="md:hidden inline-block text-on-surface dark:text-on-surface-dark"
                    x-on:click="sidebarIsOpen = true" aria-controls="sidebar" aria-expanded="false"
                    aria-label="Open sidebar">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-5"
                        aria-hidden="true">
                        <path
                            d="M0 3a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm5-1v12h9a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1zM4 2H2a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h2z" />
                    </svg>
                    <span class="sr-only">sidebar toggle</span>
                </button>

                {{-- breadcrumbs (optional) --}}
                <nav class="hidden md:inline-block text-sm font-medium" aria-label="breadcrumb">
                    <ol class="flex flex-wrap items-center gap-1">
                        <li class="flex items-center gap-1">
                            <a href="{{ route('admin.dashboard') }}"
                                class="hover:text-on-surface-strong dark:hover:text-on-surface-dark-strong">Dashboard</a>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor"
                                fill="none" stroke-width="2" class="size-4" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                            </svg>
                        </li>
                        <li class="flex items-center gap-1 font-bold text-on-surface-strong dark:text-on-surface-dark-strong"
                            aria-current="page">
                            {{ $title ?? 'Overview' }}
                        </li>
                    </ol>
                </nav>

                {{-- Profile menu (kept from your snippet) --}}
                @includeWhen(true, 'admin.partials.profile-menu')
            </nav>

            <main id="main-content" class="p-4">
                @yield('content')
            </main>
        </div>
    </div>

    @php function_exists('do_action') && do_action('admin_footer'); @endphp
</body>

</html>
