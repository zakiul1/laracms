@extends('admin.layout', ['title' => 'Menus'])

@section('content')
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">Menus</h1>
        <a href="{{ route('admin.menus.locations.index') }}" class="px-3 py-2 border rounded-radius">Manage Locations</a>
    </div>

    @if (session('success'))
        <div class="mb-3 text-green-700 bg-green-50 border border-green-200 rounded p-2">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="border rounded-radius p-4">
            <h2 class="font-medium mb-3">Create Menu</h2>
            <form method="POST" action="{{ route('admin.menus.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm mb-1">Name</label>
                    <input name="name" class="w-full border rounded-radius px-3 py-2" required>
                    @error('name')
                        <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm mb-1">Slug (optional)</label>
                    <input name="slug" class="w-full border rounded-radius px-3 py-2">
                    @error('slug')
                        <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm mb-1">Description</label>
                    <textarea name="description" class="w-full border rounded-radius px-3 py-2"></textarea>
                </div>
                <button class="px-4 py-2 border rounded-radius">Create</button>
            </form>
        </div>

        <div class="border rounded-radius p-4">
            <h2 class="font-medium mb-3">All Menus</h2>
            <div class="divide-y">
                @forelse($menus as $m)
                    <div class="py-2 flex items-center justify-between">
                        <div>
                            <div class="font-medium">{{ $m->name }}</div>
                            <div class="text-xs text-muted-foreground">{{ $m->slug }}</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a class="px-2 py-1 border rounded-radius" href="{{ route('admin.menus.edit', $m) }}">Edit</a>
                            <form method="POST" action="{{ route('admin.menus.destroy', $m) }}"
                                onsubmit="return confirm('Delete menu?');">
                                @csrf @method('DELETE')
                                <button class="px-2 py-1 border rounded-radius text-red-600">Delete</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-muted-foreground">No menus.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
