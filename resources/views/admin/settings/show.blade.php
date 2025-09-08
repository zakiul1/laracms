@extends('admin.layout', ['title' => 'Settings'])

@section('content')
    @php
        // Build tab list
        $tabItems = [];
        foreach ($tabs as $k => $meta) {
            $tabItems[] = [
                'key' => $k,
                'label' => $meta['label'] ?? ucfirst($k),
                'href' => route("admin.settings.$k"),
                'active' => $k === $pageKey,
            ];
        }

        // Preload server-side validation errors for this tab into a flat array:
        // e.g. 'general.site_title' => ['Required'] becomes ['site_title' => 'Required']
        $inlineErrors = [];
        foreach ($errors->toArray() as $name => $messages) {
            $prefix = $pageKey . '.';
            if (str_starts_with($name, $prefix)) {
                $inlineErrors[substr($name, strlen($prefix))] = $messages[0] ?? '';
            }
        }
    @endphp

    {{-- Alpine root: define page + errors so partials can safely use x-text="errors.foo" --}}
    <div x-data="{
        page: @js($pageKey),
        errors: @js($inlineErrors) || {}, // <-- prevents 'errors is not defined'
    }" class="grid grid-cols-12 gap-5">
        <aside class="col-span-12 md:col-span-3">
            <div class="border rounded bg-white overflow-hidden">
                <div class="px-3 py-2 font-medium border-b">Settings</div>
                <nav class="max-h-[70vh] overflow-auto p-2 space-y-1">
                    @foreach ($tabItems as $item)
                        <a href="{{ $item['href'] }}"
                            class="block px-3 py-2 rounded text-sm {{ $item['active'] ? 'bg-gray-100 font-medium' : 'hover:bg-gray-50' }}">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>
            </div>
        </aside>

        <main class="col-span-12 md:col-span-9">
            <div class="border rounded bg-white">
                <div class="px-4 py-3 border-b flex items-center justify-between">
                    <div class="font-medium">
                        {{ $page['label'] ?? ucfirst($pageKey) }}
                    </div>
                    <div class="flex items-center gap-2">
                        <form method="POST" action="{{ route('admin.settings.' . $pageKey . '.defaults') }}"
                            onsubmit="return confirm('Restore defaults for this page?')">
                            @csrf
                            <button class="px-3 py-1.5 border rounded text-sm" type="submit">Restore Defaults</button>
                        </form>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.settings.' . $pageKey . '.save') }}" class="p-4 space-y-4"
                    novalidate>
                    @csrf

                    {{-- Render dedicated tab view or JSON fallback --}}
                    @php $tabView = $page['view'] ?? null; @endphp
                    @if ($tabView && \Illuminate\Support\Facades\View::exists($tabView))
                        @include($tabView, ['values' => $values, 'pageKey' => $pageKey])
                    @else
                        <div class="text-sm p-3 bg-amber-50 border rounded">
                            <div class="font-medium mb-2">Developer note</div>
                            Tab view <code>{{ $tabView ?: '(none)' }}</code> not found.
                            Showing raw JSON editor fallback.
                        </div>
                        <textarea name="__json__" class="w-full h-64 border rounded p-2 text-sm" placeholder="{}">{{ json_encode($values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</textarea>
                        <script>
                            // Convert JSON textarea into POST fields on submit
                            document.currentScript.closest('form').addEventListener('submit', function(e) {
                                const ta = this.querySelector('[name="__json__"]');
                                if (!ta) return;
                                try {
                                    const obj = JSON.parse(ta.value || '{}');
                                    const inject = (prefix, val) => {
                                        if (val !== Object(val)) {
                                            const input = document.createElement('input');
                                            input.type = 'hidden';
                                            input.name = prefix;
                                            input.value = String(val);
                                            this.appendChild(input);
                                        } else {
                                            Object.keys(val).forEach(k => inject(prefix + '[' + k + ']', val[k]));
                                        }
                                    };
                                    this.querySelectorAll('input[type="hidden"]').forEach(n => {
                                        if (n.name && n.name.startsWith('_inj[')) n.remove();
                                    });
                                    const wrap = (name) => '_inj' + name;
                                    Object.keys(obj).forEach(k => inject(wrap('[' + k + ']'), obj[k]));
                                    ta.disabled = true; // prevent sending raw
                                } catch {
                                    alert('Invalid JSON. Please fix it.');
                                    e.preventDefault();
                                }
                            });
                        </script>
                    @endif

                    @if ($errors->any())
                        <div class="p-3 border rounded bg-red-50 text-sm text-red-700">
                            <div class="font-medium mb-1">Please fix the following:</div>
                            <ul class="list-disc ml-5">
                                @foreach ($errors->all() as $e)
                                    <li>{{ $e }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div>
                        <button type="submit" class="px-4 py-2 border rounded text-sm">Save Changes</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
@endsection
