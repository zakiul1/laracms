@props([
    'name' => 'media_ids',
    'multiple' => false,
    'value' => [],
])

@php
    $ids = collect((array) $value)
        ->map(fn($v) => is_array($v) ? $v['id'] ?? ($v['media_id'] ?? $v) : $v)
        ->filter()
        ->values()
        ->all();
    $uid = 'mp_' . \Illuminate\Support\Str::random(6);
@endphp

<div id="{{ $uid }}" class="space-y-2" data-name="{{ e($name) }}" data-multiple="{{ $multiple ? '1' : '0' }}"
    data-show-template="{{ route('admin.media.show', ['media' => 'MEDIA_ID']) }}">
    <div class="flex items-center gap-2">
        <button type="button" data-btn="open" class="px-3 py-2 border rounded">Choose Media</button>
        @if ($multiple)
            <button type="button" data-btn="clear" class="px-3 py-2 border rounded">Clear</button>
        @endif
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3" data-previews></div>

    <div data-inputs>
        @foreach ($ids as $id)
            <input type="hidden" name="{{ $multiple ? $name . '[]' : $name }}" value="{{ (int) $id }}">
        @endforeach
    </div>
</div>

<script>
    (function() {
        var root = document.getElementById('{{ $uid }}');
        var inputsBox = root.querySelector('[data-inputs]');
        var previews = root.querySelector('[data-previews]');
        var multiple = root.dataset.multiple === '1';
        var name = root.dataset.name;
        var showUrlT = root.dataset.showTemplate;

        var initialIds = [{{ implode(',', array_map('intval', $ids)) }}].filter(Boolean);
        var currentIds = new Set(initialIds);

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

        function buildCard(item, id) {
            var card = document.createElement('div');
            card.className = 'border rounded p-2 relative';

            var img = document.createElement('img');
            img.className = 'w-full h-28 object-cover rounded mb-2';
            img.src = (item.thumb || item.url) || '';
            card.appendChild(img);

            var cap = document.createElement('div');
            cap.className = 'text-xs truncate';
            cap.textContent = item.filename || ('#' + item.id);
            card.appendChild(cap);

            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'absolute top-1 right-1 text-xs px-2 py-0.5 border rounded bg-white';
            btn.textContent = '×';
            btn.addEventListener('click', function() {
                currentIds.delete(id);
                card.remove();
                syncInputs();
            });
            card.appendChild(btn);

            return card;
        }

        function refreshPreviews() {
            while (previews.firstChild) previews.removeChild(previews.firstChild);
            var ids = Array.from(currentIds);
            if (!ids.length) return;
            ids.forEach(function(id) {
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
                        previews.appendChild(buildCard(item, id));
                    });
            });
        }

        var openBtn = root.querySelector('[data-btn="open"]');
        openBtn.addEventListener('click', function() {
            if (typeof window.openMediaBrowser !== 'function') {
                alert('Media Browser is not loaded. Include <x-media-browser /> in your admin layout.');
                return;
            }
            window.openMediaBrowser({
                multiple: multiple,
                onSelect: function(files) {
                    if (!multiple) currentIds.clear();
                    (files || []).forEach(function(f) {
                        currentIds.add(f.id);
                    });
                    refreshPreviews();
                    syncInputs();
                }
            });
        });

        var clearBtn = root.querySelector('[data-btn="clear"]');
        if (clearBtn) clearBtn.addEventListener('click', function() {
            currentIds.clear();
            refreshPreviews();
            syncInputs();
        });

        // init
        syncInputs();
        if (currentIds.size) refreshPreviews();
    })();
</script>
