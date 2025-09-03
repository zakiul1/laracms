@extends('admin.layout', ['title' => 'Edit Menu'])

@push('head')
    {{-- SortableJS (CDN) --}}
    <script defer src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/modular/sortable.complete.esm.js" type="module">
    </script>
@endpush

@section('content')
    <div class="mb-4">
        <h1 class="text-xl font-semibold">Edit Menu — {{ $menu->name }}</h1>
    </div>

    @if (session('success'))
        <div class="mb-3 text-green-700 bg-green-50 border border-green-200 rounded p-2">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left: Add Items --}}
        <div class="lg:col-span-1 space-y-4">
            {{-- Custom Link --}}
            <div class="border rounded-radius p-4">
                <h2 class="font-medium mb-3">Add Custom Link</h2>
                <form method="POST" action="{{ route('admin.menus.items.custom.store', $menu) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-sm mb-1">URL</label>
                        <input name="url" type="url" placeholder="https://example.com"
                            class="w-full border rounded-radius px-3 py-2" required>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Link Text</label>
                        <input name="title" class="w-full border rounded-radius px-3 py-2" required>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm mb-1">Target</label>
                            <select name="target" class="w-full border rounded-radius px-3 py-2">
                                <option value="_self">Same tab</option>
                                <option value="_blank">New tab</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm mb-1">Icon (optional)</label>
                            <input name="icon" class="w-full border rounded-radius px-3 py-2" placeholder="lucide-home">
                        </div>
                    </div>
                    <button class="px-3 py-2 border rounded-radius">Add to Menu</button>
                </form>
            </div>

            {{-- Add Pages --}}
            <div class="border rounded-radius p-4 max-h-[380px] overflow-auto">
                <h2 class="font-medium mb-3">Add Pages</h2>
                <form method="POST" action="{{ route('admin.menus.items.bulk.store', $menu) }}">
                    @csrf
                    <input type="hidden" name="type" value="page">
                    <div class="space-y-2">
                        @foreach ($pages as $p)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="ids[]" value="{{ $p->id }}">
                                <span>{{ $p->title }}</span>
                            </label>
                        @endforeach
                    </div>
                    <button class="mt-3 px-3 py-2 border rounded-radius">Add to Menu</button>
                </form>
            </div>

            {{-- Add Posts --}}
            <div class="border rounded-radius p-4 max-h-[380px] overflow-auto">
                <h2 class="font-medium mb-3">Add Posts</h2>
                <form method="POST" action="{{ route('admin.menus.items.bulk.store', $menu) }}">
                    @csrf
                    <input type="hidden" name="type" value="post">
                    <div class="space-y-2">
                        @foreach ($posts as $p)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="ids[]" value="{{ $p->id }}">
                                <span>{{ $p->title }} <span
                                        class="text-xs text-muted-foreground">({{ $p->type }})</span></span>
                            </label>
                        @endforeach
                    </div>
                    <button class="mt-3 px-3 py-2 border rounded-radius">Add to Menu</button>
                </form>
            </div>

            {{-- Add Categories --}}
            <div class="border rounded-radius p-4 max-h-[380px] overflow-auto">
                <h2 class="font-medium mb-3">Add Categories</h2>
                <form method="POST" action="{{ route('admin.menus.items.bulk.store', $menu) }}">
                    @csrf
                    <input type="hidden" name="type" value="category">
                    <div class="space-y-2">
                        @foreach ($categories as $tt)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="ids[]" value="{{ $tt->id }}">
                                <span>{{ $tt->term?->name ?? 'Category #' . $tt->id }}</span>
                            </label>
                        @endforeach
                    </div>
                    <button class="mt-3 px-3 py-2 border rounded-radius">Add to Menu</button>
                </form>
            </div>
        </div>

        {{-- Right: Menu structure & settings --}}
        <div class="lg:col-span-2 space-y-6" x-data="menuEditor()">
            {{-- Menu meta --}}
            <div class="border rounded-radius p-4">
                <form method="POST" action="{{ route('admin.menus.update', $menu) }}"
                    class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @csrf @method('PATCH')
                    <div>
                        <label class="block text-sm mb-1">Name</label>
                        <input name="name" class="w-full border rounded-radius px-3 py-2" value="{{ $menu->name }}"
                            required>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Slug</label>
                        <input name="slug" class="w-full border rounded-radius px-3 py-2" value="{{ $menu->slug }}"
                            required>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Description</label>
                        <input name="description" class="w-full border rounded-radius px-3 py-2"
                            value="{{ $menu->description }}">
                    </div>
                    <div class="md:col-span-3">
                        <button class="px-3 py-2 border rounded-radius">Save Menu</button>
                    </div>
                </form>
            </div>

            {{-- Assign locations --}}
            <div class="border rounded-radius p-4">
                <form method="POST" action="{{ route('admin.menus.assign', $menu) }}" class="space-y-2">
                    @csrf
                    <div class="font-medium mb-2">Display Locations</div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        @foreach ($locations as $loc)
                            <label class="flex items-center gap-2 text-sm border rounded-radius p-2">
                                <input type="checkbox" name="locations[]" value="{{ $loc->slug }}"
                                    {{ $loc->menu_id === $menu->id ? 'checked' : '' }}>
                                <span>{{ $loc->name }} ({{ $loc->slug }})</span>
                            </label>
                        @endforeach
                    </div>
                    <button class="px-3 py-2 border rounded-radius">Save Locations</button>
                </form>
            </div>

            {{-- Structure --}}
            <div class="border rounded-radius p-4">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="font-medium">Menu Structure</h2>
                    <button class="px-3 py-2 border rounded-radius" @click="save()">Save Order</button>
                </div>

                <ul id="menu-root" class="space-y-2">
                    @foreach ($menu->roots as $item)
                        @include('admin.menus.partials.item', ['item' => $item])
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    @push('scripts')
        <script type="module">
            import Sortable from 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/modular/sortable.esm.js';

            window.menuEditor = () => ({
                save() {
                    const tree = serializeList(document.getElementById('menu-root'));
                    fetch(@json(route('admin.menus.reorder', $menu)), {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': @json(csrf_token()),
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            tree
                        })
                    }).then(r => r.json()).then(() => location.reload());
                }
            });

            function initSortable(ul) {
                new Sortable(ul, {
                    group: 'menu',
                    animation: 150,
                    fallbackOnBody: true,
                    swapThreshold: 0.65,
                    handle: '.drag-handle',
                    draggable: 'li',
                    onAdd: nestInit,
                    onUpdate: nestInit,
                });
                ul.querySelectorAll('ul').forEach(initSortable);
            }

            function nestInit() {
                /* noop for now */
            }

            function serializeList(ul) {
                const items = [];
                ul.querySelectorAll(':scope > li').forEach(li => {
                    const node = {
                        id: parseInt(li.dataset.id),
                        children: []
                    };
                    const child = li.querySelector(':scope > ul');
                    if (child) node.children = serializeList(child);
                    items.push(node);
                });
                return items;
            }
            // init root
            initSortable(document.getElementById('menu-root'));
        </script>
    @endpush
@endsection
