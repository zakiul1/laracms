@extends('admin.layout', ['title' => 'Edit Category'])

@section('content')
    @includeIf('admin.partials.flash')

    <div class="rounded-radius border border-outline dark:border-outline-dark p-4 max-w-2xl">
        <h2 class="text-lg font-semibold mb-3">Edit Category</h2>
        <form method="POST" action="{{ route('admin.categories.update', $term) }}">
            @csrf @method('PATCH')

            <label class="block text-sm font-medium mb-1">Name</label>
            <input name="name" type="text" class="w-full rounded-radius border px-3 py-2 mb-3"
                value="{{ old('name', $term->name) }}" required>

            <label class="block text-sm font-medium mb-1">Slug</label>
            <input name="slug" type="text" class="w-full rounded-radius border px-3 py-2 mb-3"
                value="{{ old('slug', $term->slug) }}">

            <label class="block text-sm font-medium mb-1">Parent</label>
            <select name="parent_id" class="w-full rounded-radius border px-3 py-2 mb-3">
                <option value="">None</option>
                @foreach ($all as $t)
                    @if ($t->id !== $term->id)
                        <option value="{{ $t->id }}" @selected(old('parent_id', $term->parent_id) == $t->id)>{{ $t->name }}</option>
                    @endif
                @endforeach
            </select>

            <label class="block text-sm font-medium mb-1">Description</label>
            <textarea name="description" rows="3" class="w-full rounded-radius border px-3 py-2 mb-3">{{ old('description', $term->meta()->where('key', 'description')->value('value')) }}</textarea>

            <div class="flex gap-2">
                <button class="px-4 py-2 bg-primary text-white rounded-radius">Update</button>
                <a href="{{ route('admin.categories.index') }}" class="px-4 py-2 border rounded-radius">Back</a>
            </div>
        </form>
    </div>
@endsection
