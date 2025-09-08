@php($w = $widget)

<div class="border rounded" data-id="{{ $w->id }}">
    <div class="px-3 py-2 flex items-center justify-between cursor-move bg-surface-alt">
        <div class="font-medium text-sm">
            {{ $w->title ?: ucfirst(str_replace('_', ' ', $w->type)) }}
            <span class="ml-1 text-[11px] opacity-60">({{ $w->type }})</span>
            @if ($w->status !== 'active')
                <span class="ml-2 text-[11px] px-2 py-0.5 rounded bg-amber-500 text-white">Inactive</span>
            @endif
        </div>

        <div class="flex items-center gap-1">
            <button class="text-xs px-2 py-0.5 border rounded text-red-600"
                @click="del({{ $w->id }})">Delete</button>
            <button type="button" class="text-xs px-2 py-0.5 border rounded"
                @click="$refs['p{{ $w->id }}'].classList.toggle('hidden')">▾</button>
        </div>
    </div>

    <div class="p-3 space-y-2 hidden" x-ref="p{{ $w->id }}">
        <input class="rounded border px-2 py-1 text-sm w-full" value="{{ $w->title }}" placeholder="Title"
            @change="save({{ $w->id }}, { title: $event.target.value })">

        @if (view()->exists('admin.appearance.widgets.types.' . $w->type))
            @include('admin.appearance.widgets.types.' . $w->type, ['widget' => $w])
        @else
            <textarea class="rounded border px-2 py-1 text-sm w-full" placeholder="Settings (JSON)"
                @change="save({{ $w->id }}, { settings: tryJson($event.target.value) })">{{ json_encode($w->settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</textarea>
        @endif

        <textarea class="rounded border px-2 py-1 text-xs w-full"
            placeholder='Visibility JSON, e.g. {"rules":["home","logged_in"],"mode":"show"}'
            @change="save({{ $w->id }}, { visibility: tryJson($event.target.value) })">{{ json_encode($w->visibility, JSON_UNESCAPED_SLASHES) }}</textarea>

        <div class="flex items-center gap-2">
            <button class="text-xs px-2 py-1 border rounded" @click="toggle({{ $w->id }})">
                {{ $w->status === 'active' ? 'Deactivate' : 'Activate' }}
            </button>
            <button class="text-xs px-2 py-1 border rounded" @click="clone({{ $w->id }})">Duplicate</button>
            <button class="text-xs px-2 py-1 border rounded text-red-600"
                @click="del({{ $w->id }})">Delete</button>
            <span class="text-xs opacity-60 ml-auto">Drag to reorder.</span>
        </div>
    </div>
</div>
