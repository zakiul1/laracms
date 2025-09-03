@extends('admin.layout', ['title' => 'Plugin Settings'])

@section('content')
    <div class="mb-4">
        <h1 class="text-xl font-semibold">{{ $plugin->name }} — Settings</h1>
        <div class="text-sm text-muted-foreground">{{ $plugin->description }}</div>
    </div>

    @if (session('success'))
        <div class="mb-3 text-green-700 bg-green-50 border border-green-200 rounded p-2">{{ session('success') }}</div>
    @endif

    @php
        $hasCustom = $customView && is_file($plugin->getBasePath() . '/' . ltrim($customView, '/'));
    @endphp

    @if ($hasCustom)
        {{-- If the plugin ships its own Blade settings, include it inside a form --}}
        <form method="POST" action="{{ route('admin.plugins.settings.save', $plugin) }}" class="space-y-4">
            @csrf
            @includeFirst(
                [
                    'plugins.' . $plugin->slug . '.settings', // if published to resources/views/plugins/<slug>/settings.blade.php
                    'admin.plugins.partials.dynamic-settings', // fallback demo
                ],
                ['plugin' => $plugin, 'pairs' => $pairs, 'meta' => $meta]
            )
            <button class="px-4 py-2 border rounded-radius">Save</button>
        </form>
    @else
        {{-- Generic key-value editor --}}
        <form method="POST" action="{{ route('admin.plugins.settings.save', $plugin) }}" class="space-y-4 max-w-2xl">
            @csrf
            <div id="kv" x-data="{ rows: @json($pairs->map(fn($s) => ['k' => $s->key, 'v' => $s->value])) }">
                <template x-for="(row,i) in rows" :key="i">
                    <div class="grid grid-cols-2 gap-3 mb-2">
                        <input x-model="row.k" name="keys[]" class="border rounded-radius px-3 py-2" placeholder="key">
                        <input x-model="row.v" name="values[]" class="border rounded-radius px-3 py-2"
                            placeholder="value (string or JSON)">
                    </div>
                </template>
                <button type="button" class="px-3 py-1 border rounded-radius" @click="rows.push({k:'',v:''})">Add</button>
            </div>
            <script>
                // server expects key-value pairs; transform on submit
                document.addEventListener('submit', function(e) {
                    const f = e.target;
                    if (!f.action.endsWith('{{ route('admin.plugins.settings.save', $plugin) }}')) return;
                    const keys = [...f.querySelectorAll('input[name="keys[]"]')].map(i => i.value);
                    const vals = [...f.querySelectorAll('input[name="values[]"]')].map(i => i.value);
                    // remove existing inputs
                    f.querySelectorAll('input[name="keys[]"],input[name="values[]"]').forEach(el => el.remove());
                    // rebuild actual payload
                    keys.forEach((k, idx) => {
                        if (!k) return;
                        const v = vals[idx] ?? '';
                        const i1 = document.createElement('input');
                        i1.type = 'hidden';
                        i1.name = k;
                        i1.value = v;
                        f.appendChild(i1);
                    });
                }, {
                    once: false
                });
            </script>
            <button class="px-4 py-2 border rounded-radius">Save</button>
        </form>
    @endif
@endsection
