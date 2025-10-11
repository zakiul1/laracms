<!-- resources/views/themes/chainlite/partials/header.blade.php -->
<header x-data="{ open: false }" x-on:keydown.escape.window="open = false"
    class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b">
    <div
        class="max-w-[1200px] mx-auto h-16 px-5
              flex items-center justify-between gap-4
              md:grid md:grid-cols-[1fr_auto_1fr]">
        {{-- Brand (left) --}}
        <a href="{{ url('/') }}"
            class="justify-self-start uppercase tracking-[.18em] font-light text-[0.95rem] whitespace-nowrap text-slate-900 hover:text-sky-700 focus:outline-none focus:ring focus:ring-sky-200 rounded">
            {{ setting('general.site_title', 'Siatex Bangladesh Ltd') }}
        </a>

        {{-- Primary nav (center / desktop) --}}
        <nav class="hidden md:block justify-self-center">
            {!! render_menu('header', [
                'ul_class' => 'flex items-center gap-8 lg:gap-10',
                'li_class' => '',
                'a_class' =>
                    'relative inline-block py-1 text-[0.95rem] text-slate-800 hover:text-sky-600 focus:outline-none focus:ring focus:ring-sky-200 rounded',
                'active_class' =>
                    'text-sky-600 after:absolute after:left-0 after:right-0 after:-bottom-2 after:h-[2px] after:bg-sky-600 after:rounded',
            ]) !!}
        </nav>

        {{-- Mobile toggle (right) --}}
        <div class="md:hidden cursor-pointer">
            <button @click="open = !open"
                class="inline-flex  items-center justify-center w-10 h-10 rounded text-slate-700 hover:bg-slate-100 focus:outline-none focus:ring cursor-pointer"
                aria-label="Toggle Menu" :aria-expanded="open.toString()" aria-controls="mobile-nav">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" stroke="currentColor"
                    stroke-width="1.5" viewBox="0 0 24 24">
                    <path d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Mobile nav (full-width under bar) --}}
    <nav id="mobile-nav" class="md:hidden border-t border-slate-200 bg-white" x-cloak x-show="open"
        x-transition.origin.top @click.outside="open = false">
        <div class="max-w-[1200px] mx-auto px-5 py-3">
            {!! render_menu('header', [
                'ul_class' => 'grid gap-2',
                'li_class' => '',
                'a_class' =>
                    'block py-2 text-[0.95rem] text-slate-800 hover:text-sky-600 rounded focus:outline-none focus:ring focus:ring-sky-200',
                'active_class' => 'text-sky-600',
            ]) !!}
        </div>
    </nav>
</header>
