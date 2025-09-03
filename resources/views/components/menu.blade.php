@php
    $level = $level ?? 0;
    $ulClass = $level === 0 ? $root_class ?? 'menu' : 'submenu';
@endphp

<ul class="{{ $ulClass }}">
    @foreach ($items as $node)
        @php
            $has = !empty($node['children']);
            $liC = trim(($li_class ?? 'menu-item') . ($has ? ' has-children' : ''));
            $href = $node['url'] ?? '#';
            $title = $node['title'] ?? 'Item';
            $target = $node['target'] ?? '_self';
            $icon = $node['icon'] ?? null;
        @endphp
        <li class="{{ $liC }}">
            <a href="{{ $href }}" target="{{ $target }}" class="{{ $a_class ?? '' }}">
                @if ($icon)
                    <i data-lucide="{{ str_replace('lucide-', '', $icon) }}" class="inline-block w-4 h-4 mr-1"></i>
                @endif
                <span>{{ $title }}</span>
            </a>

            @if ($has)
                @include('components.menu', [
                    'items' => $node['children'],
                    'options' => $options ?? [],
                    'root_class' => $root_class ?? null,
                    'li_class' => $li_class ?? null,
                    'a_class' => $a_class ?? null,
                    'level' => $level + 1,
                ])
            @endif
        </li>
    @endforeach
</ul>
