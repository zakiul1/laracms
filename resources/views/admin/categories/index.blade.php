@extends('admin.layout', ['title' => 'Categories'])

@section('content')
    @includeIf('admin.partials.flash')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Create --}}
        <div class="rounded-radius border border-outline dark:border-outline-dark p-4">
            <h2 class="text-lg font-semibold mb-3">Add New Category</h2>
            <form method="POST" action="{{ route('admin.categories.store') }}">
                @csrf
                <label class="block text-sm font-medium mb-1">Name</label>
                <input name="name" type="text" class="w-full rounded-radius border px-3 py-2 mb-3" required>

                <label class="block text-sm font-medium mb-1">Slug (optional)</label>
                <input name="slug" type="text" class="w-full rounded-radius border px-3 py-2 mb-3"
                    placeholder="auto-from-name if empty">

                <label class="block text-sm font-medium mb-1">Parent</label>
                <select name="parent_id" class="w-full rounded-radius border px-3 py-2 mb-3">
                    <option value="">None</option>
                    @foreach ($all as $t)
                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                    @endforeach
                </select>

                <label class="block text-sm font-medium mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full rounded-radius border px-3 py-2 mb-3"></textarea>

                <button class="px-4 py-2 bg-primary text-white rounded-radius">Add Category</button>
            </form>
        </div>

        {{-- List --}}
        <div class="lg:col-span-2 rounded-radius border border-outline dark:border-outline-dark overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-surface-alt dark:bg-surface-dark-alt">
                    <tr>
                        <th class="p-3 text-left">Name</th>
                        <th class="p-3 text-left">Slug</th>
                        <th class="p-3 text-left">Parent</th>
                        <th class="p-3 w-24"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $term)
                        <tr class="border-t border-outline dark:border-outline-dark">
                            <td class="p-3">{{ $term->name }}</td>
                            <td class="p-3">{{ $term->slug }}</td>
                            <td class="p-3">{{ optional($term->parent)->name }}</td>
                            <td class="p-3 text-right">
                                <a href="{{ route('admin.categories.edit', $term) }}"
                                    class="text-primary underline mr-2">Edit</a>
                                <form class="inline" method="POST" action="{{ route('admin.categories.destroy', $term) }}"
                                    onsubmit="return confirm('Delete category?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-6 text-center">No categories yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
