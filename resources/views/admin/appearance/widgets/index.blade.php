@extends('admin.layout', ['title' => 'Widgets'])

@section('content')
    @php
        $types = [];
        foreach ($registry ?? [] as $key => $class) {
            $types[$key] = $class::label();
        }
    @endphp

    <h1 class="text-xl font-semibold mb-4">Widgets</h1>

    <div x-data="wpWidgets({
        endpoints: {
            store: '{{ route('admin.appearance.widgets.store') }}',
            update: '{{ route('admin.appearance.widgets.update', 0) }}',
            toggle: '{{ route('admin.appearance.widgets.toggle', 0) }}',
            clone: '{{ route('admin.appearance.widgets.clone', 0) }}',
            delete: '{{ route('admin.appearance.widgets.delete', 0) }}',
            reorder: '{{ route('admin.appearance.widgets.reorder') }}',
            listArea: '{{ route('admin.appearance.widgets.areas.list', 0) }}',
            deleteArea: '{{ route('admin.appearance.widgets.areas.delete', 0) }}',
        },
        csrf: '{{ csrf_token() }}'
    })" class="grid grid-cols-1 lg:grid-cols-12 gap-5">

        {{-- Available Widgets (left) --}}
        <div class="lg:col-span-5">
            <div class="border rounded bg-white lg:sticky lg:top-4">
                <div class="px-3 py-2 border-b font-medium">Available Widgets</div>

                {{-- local x-data only for filtering palette --}}
                <div x-data="{ filter: '' }" class="p-3 space-y-2">
                    <input type="search" x-model="filter" class="w-full rounded border px-2 py-1 text-sm"
                        placeholder="Search widgets…">

                    <div class="space-y-2 widget-palette max-h-[70vh] overflow-y-auto pr-1" x-ref="palette">
                        @foreach ($types as $key => $label)
                            <div class="border rounded px-3 py-2 bg-surface-alt cursor-move" data-type="{{ $key }}"
                                :class="{ 'hidden': !('{{ Str::lower($label . ' ' . $key) }}'.includes(filter.toLowerCase())) }"
                                data-label="{{ $label }} {{ $key }}" title="{{ $label }}">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="font-medium text-sm">{{ $label }}</div>
                                        <div class="text-[11px] opacity-60">{{ $key }}</div>
                                    </div>
                                    <div class="text-[11px] opacity-60">drag or add →</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Widget Areas (right) --}}
        <div class="lg:col-span-7 space-y-4">

            {{-- Create Widget Area --}}
            <div class="border rounded bg-white">
                <div class="px-3 py-2 border-b font-medium">Create Widget Area</div>
                <form method="POST" action="{{ route('admin.appearance.widgets.areas.store') }}"
                    class="p-3 grid sm:grid-cols-3 gap-2">
                    @csrf
                    <input name="name" required class="rounded border px-2 py-1 text-sm"
                        placeholder="Area name (e.g. Sidebar)">
                    <input name="slug" class="rounded border px-2 py-1 text-sm" placeholder="Slug (optional)">
                    <input name="description" class="rounded border px-2 py-1 text-sm sm:col-span-3"
                        placeholder="Description (optional)">
                    <div class="sm:col-span-3">
                        <button type="submit" class="px-3 py-1.5 rounded border text-sm">Create Area</button>
                    </div>
                </form>
            </div>

            @forelse ($areas as $area)
                <div class="border rounded bg-white">
                    {{-- sticky header for each area --}}
                    <div class="px-3 py-2 border-b flex items-center justify-between sticky top-0 bg-white z-10">
                        <div>
                            <div class="font-medium">{{ $area->name }}</div>
                            <div class="text-xs opacity-70">{{ $area->slug }}</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <select class="rounded border px-2 py-1 text-sm" x-ref="select-{{ $area->id }}">
                                @foreach ($types as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="px-3 py-1.5 rounded border text-sm"
                                @click.prevent="addFromSelect({{ $area->id }}, $refs['select-{{ $area->id }}'].value)">
                                Add Widget
                            </button>
                            <button type="button" class="px-3 py-1.5 rounded border text-sm text-red-600"
                                @click.prevent="deleteArea({{ $area->id }})">
                                Delete Area
                            </button>
                        </div>
                    </div>

                    <div class="p-3">
                        {{-- scrollable list --}}
                        <div class="space-y-2 widgets-list max-h-[60vh] overflow-y-auto pr-1"
                            data-area="{{ $area->id }}" data-page="1" data-per="20" x-init="$nextTick(() => setupArea($el, {{ $area->id }}))">
                            @foreach ($area->widgets as $w)
                                @include('admin.appearance.widgets.partials.card', ['widget' => $w])
                            @endforeach

                            @if ($area->widgets->isEmpty())
                                <div class="text-sm opacity-60 px-1">
                                    No widgets placed in this area. Drag from the left or use “Add Widget”.
                                </div>
                            @endif
                        </div>

                        <div class="pt-3">
                            <button type="button" class="border rounded px-3 py-1.5 text-sm"
                                data-load="{{ $area->id }}" @click.prevent="loadMore({{ $area->id }})">
                                Load more
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                {{-- Empty state when there are no areas --}}
                <div class="border rounded bg-white p-4 text-sm opacity-70">
                    No widget areas yet. Create one above (e.g. <em>Sidebar</em>, <em>Footer</em>), then drag widgets from
                    the left or use “Add Widget”.
                </div>
            @endforelse
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Ensure the admin widgets JS is loaded --}}
    @vite('resources/js/widgets-admin.js')
@endpush
