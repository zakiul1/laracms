@props(['name' => 'gallery', 'items' => []])
@push('head')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js" defer></script>
@endpush

<div x-data="galleryComponent('{{ $name }}', @json($items))" x-init="boot()" class="space-y-2">
    <div class="flex gap-2">
        <x-ui.button type="button" @click="openMedia()" size="sm">Add images</x-ui.button>
        <span class="text-xs text-muted">Drag to reorder</span>
    </div>
    <ul id="gal-{{ Str::slug($name) }}" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
        <template x-for="(img,i) in list" :key="img.id">
            <li class="relative border rounded-radius overflow-hidden">
                <img :src="img.url" class="w-full h-24 object-cover" />
                <button type="button" class="absolute top-1 right-1 bg-black/60 text-white text-xs px-1 rounded"
                    @click="remove(i)">×</button>
                <input type="hidden" name="{{ $name }}[]" :value="img.id">
            </li>
        </template>
    </ul>
</div>

@push('scripts')
    <script>
        function galleryComponent(fieldName, initial) {
            return {
                list: initial || [],
                boot() {
                    const el = document.getElementById('gal-' + fieldName.replace(/\W+/g, '-'));
                    if (window.Sortable && el) {
                        new Sortable(el, {
                            animation: 150,
                            onEnd: () => {
                                this.list = Array.from(el.querySelectorAll('input[name="' + fieldName + '[]"]'))
                                    .map(i => {
                                        const id = parseInt(i.value, 10);
                                        const li = i.closest('li');
                                        const url = li.querySelector('img').src;
                                        return {
                                            id,
                                            url
                                        };
                                    });
                            }
                        });
                    }
                },
                openMedia() {
                    // Reuse your existing media browser trigger (set a global handler)
                    if (window.openMediaPicker) {
                        window.openMediaPicker({
                            multiple: true,
                            onSelect: (items) => {
                                for (const it of items) this.list.push({
                                    id: it.id,
                                    url: it.url
                                });
                            }
                        });
                    } else {
                        alert('Media picker not wired. Please expose window.openMediaPicker from your Media Browser.');
                    }
                },
                remove(i) {
                    this.list.splice(i, 1);
                },
            }
        }
    </script>
@endpush
