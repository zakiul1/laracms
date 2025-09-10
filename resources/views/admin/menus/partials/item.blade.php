@php
    /** @var \App\Models\MenuItem $item */
@endphp
<li data-id="{{ $item->id }}" class="border rounded-radius bg-white dark:bg-surface-dark">

    {{-- row --}}
    <div class="flex items-center justify-between px-3 py-2">
        <div class="flex items-center gap-3">
            <span class="drag-handle cursor-grab select-none text-lg leading-none">⋮⋮</span>
            <div class="text-sm">
                <div class="font-medium">{{ $item->title }}</div>
                <div class="text-xs text-muted-foreground break-all">{{ $item->url }}</div>
            </div>
        </div>
        <button type="button" class="px-2 py-1 border rounded-radius text-xs"
            @click="document.getElementById('edit-{{ $item->id }}').classList.toggle('hidden'); if (window.createIcons && window.icons) requestIdleCallback(()=>createIcons({icons}))">
            ▾ Edit
        </button>
    </div>

    {{-- editor --}}
    <div id="edit-{{ $item->id }}" class="hidden border-t px-3 py-3">
        {{-- SAVE form --}}
        <form id="save-{{ $item->id }}" method="POST"
            action="{{ route('admin.menus.items.update', [$menu, $item]) }}"
            class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @csrf @method('PATCH')
            <div>
                <label class="block text-xs mb-1">Title</label>
                <input name="title" value="{{ $item->title }}" class="w-full border rounded-radius px-2 py-1.5">
            </div>
            <div>
                <label class="block text-xs mb-1">URL</label>
                <input name="url" value="{{ $item->url }}" class="w-full border rounded-radius px-2 py-1.5">
            </div>
            <div>
                <label class="block text-xs mb-1">Target</label>
                <select name="target" class="w-full border rounded-radius px-2 py-1.5">
                    <option value="_self" @selected(($item->target ?? '_self') === '_self')>Same tab</option>
                    <option value="_blank" @selected($item->target === '_blank')>New tab</option>
                </select>
            </div>
            <div x-data="{ val: @js($item->icon) }">
                <label class="block text-xs mb-1 flex items-center gap-2">
                    Icon
                    <span class="inline-flex w-5 h-5 items-center justify-center">
                        <i x-show="val" :data-lucide="val"></i>
                    </span>
                </label>
                <input name="icon" x-model="val" placeholder="lucide-home"
                    class="w-full border rounded-radius px-2 py-1.5"
                    @input="window.createIcons && window.icons && requestIdleCallback(()=>createIcons({icons}))">
            </div>
        </form>

        {{-- DELETE form (separate) --}}
        <form id="del-{{ $item->id }}" method="POST"
            action="{{ route('admin.menus.items.destroy', [$menu, $item]) }}">
            @csrf @method('DELETE')
        </form>

        <div class="mt-2 flex items-center gap-2">
            <button type="submit" form="save-{{ $item->id }}"
                class="px-3 py-1.5 border rounded-radius">Save</button>

            <button type="submit" form="del-{{ $item->id }}"
                class="px-3 py-1.5 border border-red-300 text-red-600 rounded-radius"
                onclick="return confirm('Delete this menu item?')">Delete</button>
        </div>
    </div>

    {{-- children drop zone (ALWAYS rendered) --}}
    <ul class="children pl-6 ml-3 border-l border-dashed border-gray-300 min-h-[10px] py-2 space-y-2">
        @foreach ($item->children as $child)
            @include('admin.menus.partials.item', ['item' => $child])
        @endforeach
    </ul>
</li>
