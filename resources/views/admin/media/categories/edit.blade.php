@extends('admin.layout', ['title' => 'Edit Media Category'])

@section('content')
    <div class="max-w-xl mx-auto">
        <div class="rounded-radius border border-outline dark:border-outline-dark p-4">
            <form method="POST" action="{{ route('admin.media.categories.update', $tt) }}" class="space-y-3">
                @csrf @method('PATCH')
                <label class="block text-sm">
                    <span class="block mb-1">Name</span>
                    <input name="name" value="{{ old('name', $tt->term->name) }}" required
                        class="w-full rounded-radius border px-3 py-2" />
                </label>
                <label class="block text-sm">
                    <span class="block mb-1">Slug (optional)</span>
                    <input name="slug" value="{{ old('slug', $tt->term->slug) }}"
                        class="w-full rounded-radius border px-3 py-2" />
                </label>
                <label class="block text-sm">
                    <span class="block mb-1">Parent (optional)</span>
                    <select name="parent_id" class="w-full rounded-radius border px-3 py-2">
                        <option value="">—</option>
                        @foreach ($parents as $p)
                            <option value="{{ $p->id }}" @selected($p->id == old('parent_id', $tt->parent_id))>
                                {{ $p->term->name }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm">
                    <span class="block mb-1">Description (optional)</span>
                    <textarea name="description" class="w-full rounded-radius border px-3 py-2">{{ old('description', $tt->description) }}</textarea>
                </label>
                <div class="flex gap-2">
                    <button class="px-4 py-2 rounded-radius bg-primary text-white">Update</button>
                    <a href="{{ route('admin.media.categories.index') }}" class="px-4 py-2 rounded-radius border">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
