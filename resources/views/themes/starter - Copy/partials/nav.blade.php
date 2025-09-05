<header class="border-b border-neutral-200 bg-white/80 backdrop-blur">
    <div class="mx-auto max-w-6xl px-4 py-3 flex items-center gap-6">
        <a href="{{ url('/') }}" class="shrink-0 flex items-center gap-2">
            @if (!empty($settings?->logo_url))
                <img src="{{ $settings->logo_url }}" alt="Logo" class="h-8 w-auto">
            @endif
            <span class="text-lg font-semibold">
                {{ $settings->site_title ?? config('app.name') }}
            </span>
        </a>

        <nav class="ml-auto hidden md:block">
            <ul class="flex items-center gap-5">
                @php
                    $items = optional($mainMenu)->items ?? collect();
                    $items = is_iterable($items) ? $items : collect();
                @endphp

                @forelse($items as $item)
                    @php
                        $hasKids = isset($item->children) && count($item->children);
                        $url = $item->url ?? '#';
                        $label = $item->label ?? 'Item';
                    @endphp
                    <li class="relative group">
                        <a href="{{ $url }}"
                            class="text-sm hover:text-primary transition-colors">{{ $label }}</a>
                        @if ($hasKids)
                            <ul
                                class="absolute left-0 mt-2 hidden min-w-[220px] bg-white shadow-lg ring-1 ring-black/5 rounded-md p-2 group-hover:block">
                                @foreach ($item->children as $c)
                                    <li>
                                        <a href="{{ $c->url ?? '#' }}"
                                            class="block px-3 py-1.5 text-sm rounded hover:bg-neutral-50">
                                            {{ $c->label ?? 'Item' }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @empty
                    {{-- fallback menu --}}
                    <li><a href="{{ url('/') }}" class="text-sm">Home</a></li>
                @endforelse
            </ul>
        </nav>
    </div>
</header>
