<nav class="site-nav">
    <div class="container">
        <ul class="menu">
            {{-- Use headerMenu/mainMenu if available; else fallback --}}
            @php $menu = $headerMenu ?: $mainMenu; @endphp

            @if ($menu && $menu->items)
                @foreach ($menu->items as $item)
                    <li class="menu-item">
                        <a href="{{ $item->url ?? '#' }}">{{ $item->label ?? 'Item' }}</a>
                        @if ($item->children && $item->children->count())
                            <ul class="submenu">
                                @foreach ($item->children as $child)
                                    <li><a href="{{ $child->url ?? '#' }}">{{ $child->label ?? 'Child' }}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            @else
                <li class="menu-item"><a href="{{ url('/') }}">Home</a></li>
                <li class="menu-item"><a href="{{ url('/admin') }}">Admin</a></li>
            @endif
        </ul>
    </div>
</nav>
