@props([
    'name' => 'media_ids',
    'multiple' => false,
    'value' => [],
    'label' => 'Choose Media',
])

@php
    $ids = collect((array) $value)
        ->map(fn($v) => is_array($v) ? $v['id'] ?? ($v['media_id'] ?? $v) : $v)
        ->filter()
        ->map(fn($v) => (int) $v)
        ->values()
        ->all();

    $uid = 'mp_' . \Illuminate\Support\Str::random(6);
@endphp

<div id="{{ $uid }}" class="space-y-2" data-name="{{ e($name) }}" data-multiple="{{ $multiple ? '1' : '0' }}"
    data-show-template="{{ route('admin.media.show', ['media' => 'MEDIA_ID']) }}">

    <h3 class="font-semibold mb-2">{{ $slot->isEmpty() ? 'Featured Images' : $slot }}</h3>

    <div class="flex items-center gap-2">
        <button type="button" data-btn="open" class="px-2 py-1.5 border text-sm cursor-pointer">
            {{ $label }}
        </button>

        @if ($multiple)
            <button type="button" data-btn="clear" class="px-2 py-1.5 border text-sm cursor-pointer">
                Clear
            </button>
        @endif
    </div>

    {{-- Previews --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3" data-previews></div>

    {{-- Hidden inputs --}}
    <div data-inputs>
        @foreach ($ids as $id)
            <input type="hidden" name="{{ $multiple ? $name . '[]' : $name }}" value="{{ (int) $id }}">
        @endforeach
    </div>
</div>

<script>
    (function() {
        // Safe JSON injection
        var rootId = @json($uid);
        var initIds = @json($ids);

        var root = document.getElementById(rootId);
        if (!root) return;

        var inputsBox = root.querySelector('[data-inputs]');
        var previews = root.querySelector('[data-previews]');
        var multiple = root.dataset.multiple === '1';
        var name = root.dataset.name;
        var showUrlT = root.dataset.showTemplate;

        var currentIds = new Set((initIds || []).filter(Boolean));

        function syncInputs() {
            while (inputsBox.firstChild) inputsBox.removeChild(inputsBox.firstChild);
            if (multiple) {
                currentIds.forEach(function(id) {
                    var i = document.createElement('input');
                    i.type = 'hidden';
                    i.name = name + '[]';
                    i.value = id;
                    inputsBox.appendChild(i);
                });
            } else {
                var one = currentIds.values().next().value || '';
                var i = document.createElement('input');
                i.type = 'hidden';
                i.name = name;
                i.value = one;
                inputsBox.appendChild(i);
            }
        }

        function cardSkeleton() {
            var card = document.createElement('div');
            card.className = 'border shadow-sm p-2';
            var box = document.createElement('div');
            box.className = 'w-full aspect-square bg-gray-100 animate-pulse';
            card.appendChild(box);
            var bar = document.createElement('div');
            bar.className = 'h-3 mt-2 bg-gray-100 animate-pulse';
            card.appendChild(bar);
            return card;
        }

        function buildCard(item, id) {
            var card = document.createElement('div');
            card.className = 'border shadow-sm p-2 relative';

            // Square preview area (no rounded corners)
            var box = document.createElement('div');
            box.className = 'w-full aspect-square bg-gray-100 overflow-hidden';
            var img = document.createElement('img');
            img.className = 'w-full h-full object-cover';
            img.src = (item.thumb || item.url) || '';
            box.appendChild(img);
            card.appendChild(box);

            // Position badge (left)
            var idx = Array.from(currentIds).indexOf(id) + 1;
            var badge = document.createElement('span');
            badge.className =
                'absolute top-1 left-1 inline-flex items-center justify-center ' +
                'w-5 h-5 text-[10px] bg-black/70 text-white';
            badge.textContent = idx > 0 ? String(idx) : '';
            badge.style.cursor = 'default';
            card.appendChild(badge);

            // Single remove button (right) — small, red, same size as badge
            var rm = document.createElement('button');
            rm.type = 'button';
            rm.className =
                'absolute top-1 right-1 inline-flex items-center justify-center ' +
                'w-5 h-5 text-[11px] bg-red-600 text-white';
            rm.textContent = '×';
            rm.style.cursor = 'pointer';
            rm.addEventListener('click', function(e) {
                e.stopPropagation();
                currentIds.delete(id);
                render();
            });
            card.appendChild(rm);

            // Filename under the image
            var cap = document.createElement('div');
            cap.className = 'text-xs truncate mt-2';
            cap.textContent = item.filename || ('#' + (item.id || id));
            card.appendChild(cap);

            return card;
        }

        function render() {
            syncInputs();

            while (previews.firstChild) previews.removeChild(previews.firstChild);
            var ids = Array.from(currentIds);
            if (!ids.length) return;

            ids.forEach(function(id) {
                var sk = cardSkeleton();
                previews.appendChild(sk);

                var url = showUrlT.replace('MEDIA_ID', String(id));
                fetch(url, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    })
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(item) {
                        previews.replaceChild(buildCard(item, id), sk);
                    })
                    .catch(function() {
                        sk.remove();
                    });
            });
        }

        // Open media browser
        var openBtn = root.querySelector('[data-btn="open"]');
        if (openBtn) {
            openBtn.addEventListener('click', function() {
                if (typeof window.openMediaBrowser !== 'function') {
                    alert(
                        'Media browser is not loaded. Please include the media browser component in the admin layout.'
                        );
                    return;
                }
                window.openMediaBrowser({
                    multiple: multiple,
                    selected: Array.from(currentIds),
                    onSelect: function(files) {
                        if (!multiple) currentIds.clear();
                        (files || []).forEach(function(f) {
                            var id = f && (f.id || f.media_id);
                            if (id) currentIds.add(id);
                        });
                        render();
                    }
                });
            });
        }

        // Clear all — no confirm modal (per request)
        var clearBtn = root.querySelector('[data-btn="clear"]');
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                currentIds.clear();
                render();
            });
        }

        // Initial render
        render();
    })();
</script>
