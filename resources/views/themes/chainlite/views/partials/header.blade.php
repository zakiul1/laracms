<!-- resources/themes/chainlite/partials/header.blade.php -->
<header x-data="{ open: false }" class="sticky top-0 z-40 bg-white border-b">
    <div class="max-w-[1200px] mx-auto flex justify-between px-5  items-center h-16">

        {{-- Brand (left) --}}
        <a href="{{ url('/') }}"
            class="justify-self-start uppercase tracking-[.18em] font-light text-[0.95rem] whitespace-nowrap text-slate-900 no-underline">
            {{ setting('general.site_title', 'Siatex Bangladesh Ltd') }}
        </a>

        {{-- Primary nav (center / desktop) --}}
        <nav class="hidden md:block justify-self-center ">
            {!! render_menu('header', [
                'ul_class' => 'flex items-center gap-8 lg:gap-10',
                'li_class' => '',
                // base link style
                'a_class' => 'relative inline-block py-1 text-[0.9rem] text-slate-800 hover:text-sky-600',
                // how the active item looks (pure Tailwind)
                'active_class' =>
                    'text-sky-600 after:block after:absolute after:inset-x-0 after:-bottom-2 after:h-[2px] after:bg-sky-600 after:rounded',
            ]) !!}
        </nav>

        {{-- Mobile toggle (right) --}}
        <div class="justify-self-end md:hidden">
            <button @click="open = !open"
                class="inline-flex items-center justify-center w-10 h-10 rounded text-slate-700 hover:bg-slate-100"
                aria-label="Toggle Menu" :aria-expanded="open">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" stroke="currentColor"
                    stroke-width="1.5" viewBox="0 0 24 24">
                    <path d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Mobile nav (full-width under bar) --}}
    <nav class="md:hidden border-t border-slate-200 bg-white" x-cloak x-show="open" x-transition.origin.top>
        {!! render_menu('header', [
            'ul_class' => 'grid gap-2 px-5 py-3',
            'li_class' => '',
            'a_class' => 'block py-2 text-[0.95rem] text-slate-800 hover:text-sky-600',
            'active_class' => 'text-sky-600',
        ]) !!}
    </nav>
</header>
