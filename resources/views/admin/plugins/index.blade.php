@extends('admin.layout', ['title' => 'Plugins'])

@section('content')
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">Plugins</h1>
        <div class="flex gap-2">
            <form method="POST" action="{{ route('admin.plugins.sync') }}">
                @csrf
                <button class="px-3 py-2 border rounded-radius">Sync</button>
            </form>
            <a href="{{ route('admin.plugins.upload.form') }}" class="px-3 py-2 border rounded-radius">Upload</a>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-3 text-green-700 bg-green-50 border border-green-200 rounded p-2">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-3 text-red-700 bg-red-50 border border-red-200 rounded p-2">{{ session('error') }}</div>
    @endif

    <div class="overflow-x-auto rounded-radius border">
        <table class="min-w-full text-sm">
            <thead class="bg-muted/50">
                <tr class="text-left">
                    <th class="p-3">Name</th>
                    <th class="p-3">Version</th>
                    <th class="p-3">Author</th>
                    <th class="p-3">Status</th>
                    <th class="p-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($plugins as $p)
                    <tr class="border-t">
                        <td class="p-3">
                            <div class="font-medium">{{ $p->name }}</div>
                            <div class="text-xs text-muted-foreground">{{ $p->slug }}</div>
                            @if ($p->description)
                                <div class="text-xs mt-1">{{ Str::limit($p->description, 120) }}</div>
                            @endif
                        </td>
                        <td class="p-3">{{ $p->version ?: '—' }}</td>
                        <td class="p-3">
                            @if ($p->homepage)
                                <a class="underline" href="{{ $p->homepage }}" target="_blank"
                                    rel="noreferrer">{{ $p->author ?: '—' }}</a>
                            @else
                                {{ $p->author ?: '—' }}
                            @endif
                        </td>
                        <td class="p-3">
                            @if ($p->enabled)
                                <span
                                    class="inline-flex items-center px-2 py-0.5 text-xs rounded bg-green-50 text-green-700 border border-green-200">Active</span>
                            @else
                                <span
                                    class="inline-flex items-center px-2 py-0.5 text-xs rounded bg-gray-50 text-gray-700 border border-gray-200">Inactive</span>
                            @endif
                        </td>
                        <td class="p-3">
                            <div class="flex flex-wrap gap-2">
                                @if (!$p->enabled)
                                    <form method="POST" action="{{ route('admin.plugins.activate', $p) }}">
                                        @csrf
                                        <button class="px-2 py-1 border rounded-radius">Activate</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.plugins.deactivate', $p) }}">
                                        @csrf
                                        <button class="px-2 py-1 border rounded-radius">Deactivate</button>
                                    </form>
                                @endif

                                <a href="{{ route('admin.plugins.settings', $p) }}"
                                    class="px-2 py-1 border rounded-radius">Settings</a>

                                <form method="POST" action="{{ route('admin.plugins.update.remote', $p) }}">
                                    @csrf
                                    <input type="hidden" name="url" value="{{ $p->update_url }}">
                                    <button class="px-2 py-1 border rounded-radius">Update (Remote)</button>
                                </form>

                                <label class="px-2 py-1 border rounded-radius cursor-pointer">
                                    <span>Update (ZIP)</span>
                                    <form method="POST" action="{{ route('admin.plugins.update.upload', $p) }}"
                                        enctype="multipart/form-data">
                                        @csrf
                                        <input type="file" name="zip" accept=".zip" class="hidden"
                                            onchange="this.form.submit()">
                                    </form>
                                </label>

                                <form method="POST" action="{{ route('admin.plugins.destroy', $p) }}"
                                    onsubmit="return confirm('Delete plugin? This will remove files.');">
                                    @csrf @method('DELETE')
                                    <button class="px-2 py-1 border rounded-radius text-red-600">Delete</button>
                                </form>

                                <a class="px-2 py-1 border rounded-radius"
                                    href="{{ route('admin.plugins.export', $p) }}">Export</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="p-4 text-center text-muted-foreground" colspan="5">No plugins found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
