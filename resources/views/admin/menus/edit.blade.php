@extends('admin.layout', ['title' => 'Edit Menu'])

@section('content')
    <div class="mb-4">
        <h1 class="text-xl font-semibold">Edit Menu — {{ $menu->name }}</h1>
    </div>

    @if (session('success'))
        <div class="mb-3 text-green-700 bg-green-50 border border-green-200 rounded p-2">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- LEFT COLUMN --}}
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
                        <div x-data="{ val: '' }" x-init="val = ''">
                            <label class="block text-sm mb-1 flex items-center gap-2">
                                Icon (optional)
                                <span class="inline-flex w-5 h-5 items-center justify-center">
                                    <i x-show="val" :data-lucide="val"></i>
                                </span>
                            </label>
                            <input name="icon" x-model="val" class="w-full border rounded-radius px-3 py-2"
                                placeholder="lucide-home">
                        </div>
                    </div>
                    <p class="text-xs text-muted-foreground">Target controls whether the link opens in the same tab
                        (<code>_self</code>) or a new tab (<code>_blank</code>).</p>
                    <button class="px-3 py-2 border rounded-radius">Add to Menu</button>
                </form>
            </div>

            {{-- Add Pages --}}
            <div class="border rounded-radius p-4" x-data="{ q: '' }">
                <h2 class="font-medium mb-3">Add Pages</h2>
                <input x-model="q" class="w-full border rounded-radius px-3 py-2 mb-2" placeholder="Search pages…">
                <div class="max-h-96 overflow-auto space-y-2">
                    <form method="POST" action="{{ route('admin.menus.items.bulk.store', $menu) }}" class="space-y-2">
                        @csrf
                        <input type="hidden" name="type" value="page">
                        @foreach ($pages as $p)
                            <label class="flex items-center gap-2 text-sm"
                                x-show="'{{ Str::lower($p->title) }}'.includes(q.toLowerCase())">
                                <input type="checkbox" name="ids[]" value="{{ $p->id }}">
                                <span>{{ $p->title }}</span>
                            </label>
                        @endforeach
                        <button class="mt-3 px-3 py-2 border rounded-radius">Add to Menu</button>
                    </form>
                </div>
            </div>

            {{-- Add Posts --}}
            <div class="border rounded-radius p-4" x-data="{ q: '' }">
                <h2 class="font-medium mb-3">Add Posts</h2>
                <input x-model="q" class="w-full border rounded-radius px-3 py-2 mb-2" placeholder="Search posts…">
                <div class="max-h-96 overflow-auto space-y-2">
                    <form method="POST" action="{{ route('admin.menus.items.bulk.store', $menu) }}" class="space-y-2">
                        @csrf
                        <input type="hidden" name="type" value="post">
                        @foreach ($posts as $p)
                            <label class="flex items-center gap-2 text-sm"
                                x-show="'{{ Str::lower($p->title) }}'.includes(q.toLowerCase())">
                                <input type="checkbox" name="ids[]" value="{{ $p->id }}">
                                <span>{{ $p->title }} <span
                                        class="text-xs text-muted-foreground">({{ $p->type }})</span></span>
                            </label>
                        @endforeach
                        <button class="mt-3 px-3 py-2 border rounded-radius">Add to Menu</button>
                    </form>
                </div>
            </div>

            {{-- Add Categories --}}
            <div class="border rounded-radius p-4" x-data="{ q: '' }">
                <h2 class="font-medium mb-3">Add Categories</h2>
                <input x-model="q" class="w-full border rounded-radius px-3 py-2 mb-2" placeholder="Search categories…">
                <div class="max-h-96 overflow-auto space-y-2">
                    <form method="POST" action="{{ route('admin.menus.items.bulk.store', $menu) }}" class="space-y-2">
                        @csrf
                        <input type="hidden" name="type" value="category">
                        @foreach ($categories as $tt)
                            @php $name = $tt->term?->name ?? ('Category #'.$tt->id); @endphp
                            <label class="flex items-center gap-2 text-sm"
                                x-show="'{{ Str::lower($name) }}'.includes(q.toLowerCase())">
                                <input type="checkbox" name="ids[]" value="{{ $tt->id }}">
                                <span>{{ $name }}</span>
                            </label>
                        @endforeach
                        <button class="mt-3 px-3 py-2 border rounded-radius">Add to Menu</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN --}}
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
                <form method="POST" action="{{ route('admin.menus.assign') }}" class="space-y-2">
                    @csrf
                    <input type="hidden" name="menu_id" value="{{ $menu->id }}">
                    <div class="font-medium mb-2">Display Locations</div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        @foreach ($locations as $loc)
                            <label class="flex items-center gap-2 text-sm border rounded-radius p-2">
                                <input type="checkbox" name="assign[{{ $loc->slug }}]" value="1"
                                    @checked($loc->menu_id === $menu->id)>
                                <span>{{ $loc->name }} ({{ $loc->slug }})</span>
                            </label>
                        @endforeach
                    </div>
                    <button class="px-3 py-2 border rounded-radius">Save Locations</button>
                </form>
            </div>

            {{-- Structure / reorder --}}
            <div class="border rounded-radius p-4">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="font-medium">Menu Structure</h2>
                    <button type="button" class="px-3 py-2 border rounded-radius" @click="save()">Save Order</button>
                </div>

                <ul id="menu-root" class="space-y-2" data-reorder-url="{{ route('admin.menus.items.reorder', $menu) }}"
                    data-csrf="{{ csrf_token() }}">
                    @foreach ($menu->roots as $item)
                        @include('admin.menus.partials.item', ['item' => $item])
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/admin/menus-edit.js')
@endpush
