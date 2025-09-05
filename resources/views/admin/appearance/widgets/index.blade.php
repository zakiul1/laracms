@extends('admin.layout', ['title' => 'Widgets'])

@section('content')
    @php
        use Illuminate\Support\Facades\Route as R;

        // Fallbacks so this view never crashes if routes aren’t ready yet
        $hasStore = R::has('admin.appearance.widgets.store');
        $hasUpdate = R::has('admin.appearance.widgets.update');
        $hasDestroy = R::has('admin.appearance.widgets.destroy');
        $hasCustomize = R::has('admin.appearance.customizer');

        // Tolerant inputs from controller; provide sane defaults so page loads
        $availableWidgets = $availableWidgets ?? [
            ['type' => 'text', 'label' => 'Text'],
            ['type' => 'html', 'label' => 'Custom HTML'],
            ['type' => 'menu', 'label' => 'Menu'],
            ['type' => 'recent', 'label' => 'Recent Posts'],
            ['type' => 'categories', 'label' => 'Categories'],
        ];

        // $areas can be array or collection; each area should have: id, name, slug, description
        // and optionally ->widgets (array/collection of placed widgets with id,type,settings)
        $areas = $areas ?? collect([]);
    @endphp

    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">Widgets</h1>

        @if ($hasCustomize)
            <a href="{{ route('admin.appearance.customizer') }}"
                class="px-3 py-2 rounded-radius bg-primary text-white text-sm">
                Customize
            </a>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Left: Available widgets --}}
        <section class="lg:col-span-1 rounded-radius border border-outline dark:border-outline-dark">
            <div class="p-3 border-b border-outline dark:border-outline-dark">
                <h2 class="font-medium">Available Widgets</h2>
            </div>

            <ul class="p-3 space-y-2">
                @foreach ($availableWidgets as $w)
                    <li class="border rounded-radius px-3 py-2 flex items-center justify-between">
                        <div class="text-sm">
                            <div class="font-medium">{{ $w['label'] ?? ucfirst($w['type']) }}</div>
                            <div class="text-[11px] opacity-60">{{ $w['type'] }}</div>
                        </div>
                        <span class="text-[11px] opacity-60">drag or add →</span>
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- Right: Widget Areas --}}
        <section class="lg:col-span-2 space-y-4">
            @forelse ($areas as $area)
                @php
                    $areaWidgets = collect(data_get($area, 'widgets', []));
                @endphp

                <div class="rounded-radius border border-outline dark:border-outline-dark overflow-hidden">
                    <div
                        class="p-3 border-b border-outline dark:border-outline-dark bg-surface-alt dark:bg-surface-dark-alt">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="font-semibold">{{ data_get($area, 'name', 'Sidebar') }}</h3>
                                <div class="text-[11px] opacity-60">
                                    {{ data_get($area, 'description', data_get($area, 'slug', '')) }}
                                </div>
                            </div>
                            @if ($hasStore)
                                <form method="POST" action="{{ route('admin.appearance.widgets.store') }}"
                                    class="flex items-center gap-2">
                                    @csrf
                                    <input type="hidden" name="area_id" value="{{ data_get($area, 'id') }}">
                                    <select name="type"
                                        class="border border-outline rounded-radius bg-surface px-2 py-1.5 text-sm dark:border-outline-dark dark:bg-surface-dark/50">
                                        @foreach ($availableWidgets as $w)
                                            <option value="{{ $w['type'] }}">{{ $w['label'] ?? ucfirst($w['type']) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button class="px-3 py-1.5 rounded-radius border border-outline text-sm">
                                        Add Widget
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="p-3 space-y-3">
                        @forelse ($areaWidgets as $inst)
                            @php
                                $wid = data_get($inst, 'id');
                                $wtype = data_get($inst, 'type', 'text');
                                $label = ucfirst($wtype) . ' Widget';
                                $settings = (array) data_get($inst, 'settings', []);
                            @endphp
                            <div class="rounded-radius border border-outline dark:border-outline-dark">
                                <div class="p-3 flex items-center justify-between bg-surface-alt dark:bg-surface-dark-alt">
                                    <div class="text-sm font-medium">{{ $label }}</div>
                                    <div class="flex items-center gap-2">
                                        @if ($hasDestroy && $wid)
                                            <form method="POST"
                                                action="{{ route('admin.appearance.widgets.destroy', $wid) }}"
                                                onsubmit="return confirm('Remove this widget?');">
                                                @csrf @method('DELETE')
                                                <button class="text-red-600 text-sm hover:underline">Remove</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>

                                {{-- Basic settings form (only shows for known simple widgets).
                                     Adjust fields per widget type in your controller later. --}}
                                <div class="p-3">
                                    @if ($hasUpdate && $wid)
                                        <form method="POST" action="{{ route('admin.appearance.widgets.update', $wid) }}"
                                            class="space-y-2">
                                            @csrf @method('PATCH')

                                            @if ($wtype === 'text')
                                                <label class="block text-xs">Title</label>
                                                <input type="text" name="settings[title]"
                                                    value="{{ $settings['title'] ?? '' }}"
                                                    class="w-full border border-outline rounded-radius bg-surface px-2 py-1.5 text-sm
                                                              dark:border-outline-dark dark:bg-surface-dark/50">
                                                <label class="block text-xs">Content</label>
                                                <textarea name="settings[content]" rows="3"
                                                    class="w-full border border-outline rounded-radius bg-surface px-2 py-1.5 text-sm
                                                                 dark:border-outline-dark dark:bg-surface-dark/50">{{ $settings['content'] ?? '' }}</textarea>
                                            @elseif ($wtype === 'html')
                                                <label class="block text-xs">HTML</label>
                                                <textarea name="settings[html]" rows="4"
                                                    class="w-full border border-outline rounded-radius bg-surface px-2 py-1.5 text-sm
                                                                 dark:border-outline-dark dark:bg-surface-dark/50">{{ $settings['html'] ?? '' }}</textarea>
                                            @elseif ($wtype === 'menu')
                                                <label class="block text-xs">Menu ID</label>
                                                <input type="number" name="settings[menu_id]"
                                                    value="{{ $settings['menu_id'] ?? '' }}"
                                                    class="w-full border border-outline rounded-radius bg-surface px-2 py-1.5 text-sm
                                                              dark:border-outline-dark dark:bg-surface-dark/50">
                                            @elseif ($wtype === 'recent')
                                                <label class="block text-xs">Count</label>
                                                <input type="number" name="settings[count]"
                                                    value="{{ $settings['count'] ?? 5 }}"
                                                    class="w-full border border-outline rounded-radius bg-surface px-2 py-1.5 text-sm
                                                              dark:border-outline-dark dark:bg-surface-dark/50">
                                            @elseif ($wtype === 'categories')
                                                <label class="block text-xs">Show Count</label>
                                                <select name="settings[show_count]"
                                                    class="w-full border border-outline rounded-radius bg-surface px-2 py-1.5 text-sm
                                                               dark:border-outline-dark dark:bg-surface-dark/50">
                                                    <option value="0" @selected(($settings['show_count'] ?? 0) == 0)>No</option>
                                                    <option value="1" @selected(($settings['show_count'] ?? 0) == 1)>Yes</option>
                                                </select>
                                            @endif

                                            <div class="pt-1">
                                                <button class="px-3 py-1.5 rounded-radius border border-outline text-sm">
                                                    Save
                                                </button>
                                            </div>
                                        </form>
                                    @else
                                        <div class="text-xs opacity-70 p-2">
                                            (Settings form will appear once widget routes are wired.)
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-sm opacity-70">No widgets placed in this area.</div>
                        @endforelse
                    </div>
                </div>
            @empty
                <div class="rounded-radius border border-outline dark:border-outline-dark p-6 text-center">
                    No widget areas registered yet.
                </div>
            @endforelse
        </section>
    </div>
@endsection
