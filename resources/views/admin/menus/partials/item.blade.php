<li data-id="{{ $item->id }}" class="border rounded-radius">
    <div class="flex items-center gap-2 p-2">
        <button type="button" class="drag-handle cursor-move" title="Drag">
            <i data-lucide="grip-vertical" class="w-4 h-4"></i>
        </button>
        <div class="flex-1">
            <div class="font-medium text-sm">{{ $item->title }}</div>
            <div class="text-xs text-muted-foreground">{{ $item->url ?? '#' }}</div>
        </div>
        <details class="ml-auto">
            <summary class="text-xs px-2 py-1 border rounded-radius cursor-pointer select-none">Edit</summary>
            <div class="p-3">
                <form method="POST" action="{{ route('admin.menus.items.update', [$item->menu_id, $item->id]) }}"
                    class="space-y-2">
                    @csrf @method('PATCH')
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs mb-1">Title</label>
                            <input name="title" class="w-full border rounded-radius px-2 py-1"
                                value="{{ $item->title }}">
                        </div>
                        <div>
                            <label class="block text-xs mb-1">URL</label>
                            <input name="url" class="w-full border rounded-radius px-2 py-1"
                                value="{{ $item->url }}">
                        </div>
                        <div>
                            <label class="block text-xs mb-1">Target</label>
                            <select name="target" class="w-full border rounded-radius px-2 py-1">
                                <option value="_self" {{ $item->target === '_self' ? 'selected' : '' }}>Same tab
                                </option>
                                <option value="_blank" {{ $item->target === '_blank' ? 'selected' : '' }}>New tab
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs mb-1">Icon</label>
                            <input name="icon" class="w-full border rounded-radius px-2 py-1"
                                value="{{ $item->icon }}">
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button class="px-2 py-1 border rounded-radius">Save</button>
                        <form method="POST"
                            action="{{ route('admin.menus.items.destroy', [$item->menu_id, $item->id]) }}"
                            onsubmit="return confirm('Delete item (and its children)?');">
                            @csrf @method('DELETE')
                            <button class="px-2 py-1 border rounded-radius text-red-600">Delete</button>
                        </form>
                    </div>
                </form>
            </div>
        </details>
    </div>

    @if ($item->children && $item->children->count())
        <ul class="ml-6 my-2 space-y-2">
            @foreach ($item->children as $child)
                @include('admin.menus.partials.item', ['item' => $child])
            @endforeach
        </ul>
    @else
        <ul class="ml-6 my-2 space-y-2"></ul>
    @endif
</li>
