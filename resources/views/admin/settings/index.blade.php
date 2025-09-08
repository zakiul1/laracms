@extends('admin.layout', ['title' => 'Settings'])

@section('content')
    <h1 class="text-xl font-semibold mb-4">Settings</h1>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5" x-data="settingsForm({
        saveUrl: '{{ route('admin.settings.save', $activeTab) }}',
        restoreUrl: '{{ route('admin.settings.restore', $activeTab) }}',
        csrf: '{{ csrf_token() }}',
    })">

        {{-- Tabs --}}
        <nav class="lg:col-span-3">
            <div class="border rounded bg-white overflow-hidden">
                @foreach ($pagesDef as $k => $pg)
                    <a href="{{ route('admin.settings.index', $k) }}"
                        class="flex items-center gap-2 px-3 py-2 border-b last:border-0
                              {{ $activeTab === $k ? 'bg-surface-alt' : 'hover:bg-surface-alt' }}">
                        <span class="text-sm">{{ $pg['label'] }}</span>
                    </a>
                @endforeach
            </div>

            <div class="mt-4 border rounded bg-white p-3">
                <div class="font-medium text-sm mb-2">Tools</div>
                <form action="{{ route('admin.settings.export') }}" method="GET" class="mb-2">
                    <button class="px-3 py-1.5 rounded border text-sm w-full" type="submit">Export JSON</button>
                </form>
                <form action="{{ route('admin.settings.import') }}" method="POST" class="space-y-2">
                    @csrf
                    <textarea name="json" rows="3" class="w-full rounded border px-2 py-1 text-xs"
                        placeholder="Paste settings JSON..."></textarea>
                    <button class="px-3 py-1.5 rounded border text-sm w-full" type="submit">Import JSON</button>
                </form>
            </div>
        </nav>

        {{-- Active tab content --}}
        <section class="lg:col-span-9">
            <div class="border rounded bg-white">
                <div class="px-3 py-2 border-b flex items-center justify-between">
                    <div class="font-medium">{{ $pagesDef[$activeTab]['label'] ?? ucfirst($activeTab) }}</div>
                    <div class="flex items-center gap-2">
                        <button type="button" class="px-3 py-1.5 rounded border text-sm" @click="submit()">Save</button>
                        <form method="POST" action="{{ route('admin.settings.restore', $activeTab) }}"
                            onsubmit="return confirm('Restore defaults?')">
                            @csrf
                            <button class="px-3 py-1.5 rounded border text-sm">Restore Defaults</button>
                        </form>
                    </div>
                </div>

                <form x-ref="form" class="p-4 space-y-4">
                    @includeIf('admin.settings.tabs.' . $activeTab, [
                        'values' => $values,
                        'timezones' => $timezones,
                        'pagesForSelect' => $pagesForSelect,
                    ])
                </form>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/settings.js')
@endpush
