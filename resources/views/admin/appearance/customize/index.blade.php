@extends('admin.layout', ['title' => 'Customizer'])

@section('content')
    @php
        $schema = $schema ?? [];
        $sections = $schema['sections'] ?? [];
    @endphp

    <div class="grid grid-cols-12 gap-5">
        <aside class="col-span-12 md:col-span-4">
            <div class="border rounded bg-white">
                <div class="px-3 py-2 border-b font-medium">
                    Theme Customizer
                    @if (!empty($activeThemeTitle))
                        <span class="text-xs opacity-60">({{ $activeThemeTitle }} — {{ $activeThemeSlug }})</span>
                    @endif
                </div>

                <form method="POST" action="{{ route('admin.appearance.customize.save') }}" class="p-3 space-y-4"
                    x-data="customizerForm()">
                    @csrf

                    @if (empty($sections))
                        {{-- Fallback generic fields when theme has no customizer.php --}}
                        <div>
                            <label class="text-xs">Site Title</label>
                            <input class="w-full rounded border px-2 py-1 text-sm" name="settings[site_title]"
                                value="{{ $settings['site_title'] ?? '' }}"
                                @input="debounceField('site_title', $event.target.value)">
                        </div>

                        <div>
                            <label class="text-xs">Primary Color</label>
                            <input type="color" class="rounded border px-2 py-1 text-sm" name="settings[primary]"
                                value="{{ $settings['primary'] ?? '#0ea5e9' }}"
                                @input="debounceField('primary', $event.target.value)">
                        </div>

                        <div>
                            <label class="text-xs">Accent Color</label>
                            <input type="color" class="rounded border px-2 py-1 text-sm" name="settings[accent]"
                                value="{{ $settings['accent'] ?? '#f97316' }}"
                                @input="debounceField('accent', $event.target.value)">
                        </div>

                        <div>
                            <label class="text-xs">Custom CSS</label>
                            <textarea name="settings[custom_css]" rows="5" class="w-full rounded border px-2 py-1 text-sm"
                                @input="debounceField('custom_css', $event.target.value)">{{ $settings['custom_css'] ?? '' }}</textarea>
                        </div>
                    @else
                        {{-- Render schema-based sections/fields --}}
                        @foreach ($sections as $sectionKey => $section)
                            <div class="border rounded p-2">
                                <div class="text-sm font-medium mb-2">{{ $section['label'] ?? ucfirst($sectionKey) }}</div>
                                <div class="space-y-3">
                                    @foreach ($section['fields'] ?? [] as $key => $def)
                                        @php
                                            $label = $def['label'] ?? ucfirst($key);
                                            $type = $def['type'] ?? 'text';
                                            $name = "settings[$key]";
                                            $value = $settings[$key] ?? ($def['default'] ?? '');
                                        @endphp

                                        <div>
                                            <label class="text-xs">{{ $label }}</label>

                                            @if ($type === 'textarea')
                                                <textarea class="w-full rounded border px-2 py-1 text-sm" name="{{ $name }}"
                                                    @input="debounceField('{{ $key }}', $event.target.value)">{{ $value }}</textarea>
                                            @elseif($type === 'select')
                                                <select class="w-full rounded border px-2 py-1 text-sm"
                                                    name="{{ $name }}"
                                                    @change="debounceField('{{ $key }}', $event.target.value)">
                                                    @foreach ($def['choices'] ?? [] as $optVal => $optLabel)
                                                        <option value="{{ $optVal }}" @selected($value == $optVal)>
                                                            {{ $optLabel }}</option>
                                                    @endforeach
                                                </select>
                                            @elseif($type === 'checkbox')
                                                <label class="inline-flex items-center gap-2">
                                                    <input type="hidden" name="{{ $name }}" value="0">
                                                    <input type="checkbox" name="{{ $name }}" value="1"
                                                        @change="debounceField('{{ $key }}', $event.target.checked ? 1 : 0)"
                                                        {{ (int) $value ? 'checked' : '' }}>
                                                    <span class="text-xs">{{ $def['text'] ?? '' }}</span>
                                                </label>
                                            @elseif($type === 'color')
                                                <input type="color" class="rounded border px-2 py-1 text-sm"
                                                    name="{{ $name }}" value="{{ $value }}"
                                                    @input="debounceField('{{ $key }}', $event.target.value)">
                                            @elseif($type === 'number')
                                                <input type="number" class="w-full rounded border px-2 py-1 text-sm"
                                                    name="{{ $name }}" value="{{ $value }}"
                                                    min="{{ $def['min'] ?? '' }}" max="{{ $def['max'] ?? '' }}"
                                                    @input="debounceField('{{ $key }}', $event.target.value)">
                                            @elseif($type === 'image')
                                                <input type="url" class="w-full rounded border px-2 py-1 text-sm"
                                                    name="{{ $name }}" value="{{ $value }}"
                                                    placeholder="https://example.com/logo.png"
                                                    @input="debounceField('{{ $key }}', $event.target.value)">
                                                {{-- Hook your media picker button here if desired --}}
                                            @else
                                                <input type="text" class="w-full rounded border px-2 py-1 text-sm"
                                                    name="{{ $name }}" value="{{ $value }}"
                                                    @input="debounceField('{{ $key }}', $event.target.value)">
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
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

                    <div class="flex items-center gap-2">
                        <button type="submit" class="px-3 py-1.5 border rounded text-sm">Save</button>
                        <a href="{{ $previewUrl }}" target="customizerPreview"
                            class="px-3 py-1.5 border rounded text-sm">
                            Refresh Preview
                        </a>
                    </div>

                    <script>
                        function customizerForm() {
                            let timer = null;
                            const send = (payload) => {
                                const frame = document.querySelector('iframe[name="customizerPreview"]');
                                if (frame && frame.contentWindow) {
                                    frame.contentWindow.postMessage({
                                        type: 'customizer:update',
                                        payload
                                    }, '*');
                                }
                            };
                            const cache = {};
                            return {
                                debounceField(key, val) {
                                    cache[key] = val;
                                    clearTimeout(timer);
                                    timer = setTimeout(() => send(cache), 180);
                                }
                            };
                        }
                    </script>
                </form>
            </div>
        </aside>

        <main class="col-span-12 md:col-span-8">
            <div class="border rounded bg-white h-[70vh]">
                <div class="px-3 py-2 border-b font-medium flex items-center justify-between">
                    <span>Live Preview</span>
                    <a href="{{ $previewUrl }}" target="_blank" class="text-xs underline">Open in new tab</a>
                </div>
                <iframe src="{{ $previewUrl }}" name="customizerPreview" class="w-full h-[calc(70vh-40px)]"
                    style="border:0" loading="lazy"></iframe>
            </div>
        </main>
    </div>
@endsection
