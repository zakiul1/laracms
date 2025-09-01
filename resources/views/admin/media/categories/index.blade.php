{{-- resources/views/admin/media/categories/index.blade.php --}}
@extends('admin.layout')

@section('content')
    <div class="max-w-6xl mx-auto space-y-6">
        {{-- Page header --}}
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-semibold">Media Categories</h1>

            <a href="{{ route('admin.media.categories.create') }}"
                class="ml-auto inline-flex items-center rounded-radius bg-primary px-4 py-2 text-sm font-medium text-on-primary hover:opacity-90 dark:bg-primary-dark dark:text-on-primary-dark">
                + Add Category
            </a>
        </div>

        {{-- Flash messages --}}
        @if (session('status'))
            <div
                class="rounded-radius border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-800 dark:border-green-900/50 dark:bg-green-900/30 dark:text-green-200">
                {{ session('status') }}
            </div>
        @endif
        @if (session('error'))
            <div
                class="rounded-radius border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-900/30 dark:text-red-200">
                {{ session('error') }}
            </div>
        @endif

        {{-- Help --}}
        <p class="text-sm text-on-surface/70 dark:text-on-surface-dark/70">
            Create and manage categories used to organize files in the media library. Parent/child relationships are
            supported.
        </p>

        {{-- Categories table --}}
        <div
            class="overflow-x-auto rounded-radius border border-outline/70 bg-surface dark:border-outline-dark/70 dark:bg-surface-dark/40">
            <table class="min-w-full text-sm">
                <thead
                    class="bg-surface-alt/60 text-on-surface-strong dark:bg-surface-dark-alt/50 dark:text-on-surface-dark-strong">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Name</th>
                        <th class="px-4 py-3 text-left font-semibold">Slug</th>
                        <th class="px-4 py-3 text-left font-semibold">Parent</th>
                        <th class="px-4 py-3 text-left font-semibold">Description</th>
                        <th class="px-4 py-3 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline/50 dark:divide-outline-dark/50">
                    @forelse($cats as $cat)
                        @php
                            $name = $cat->term?->name ?? '—';
                            $slug = $cat->term?->slug ?? '—';
                            $parentName = $cat->parent?->term?->name ?? '—';
                            $desc = $cat->description ?: '—';
                        @endphp
                        <tr>
                            <td class="px-4 py-3 align-top">
                                <div class="font-medium">{{ $name }}</div>
                                <div class="text-xs text-on-surface/60 dark:text-on-surface-dark/60">#{{ $cat->id }}
                                </div>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <code
                                    class="rounded bg-black/5 px-1.5 py-0.5 text-xs dark:bg-white/10">{{ $slug }}</code>
                            </td>
                            <td class="px-4 py-3 align-top">{{ $parentName }}</td>
                            <td class="px-4 py-3 align-top">
                                <div class="max-w-[40ch] truncate" title="{{ $desc }}">{{ $desc }}</div>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.media.categories.edit', $cat) }}"
                                        class="rounded-radius border border-outline px-3 py-1.5 text-xs font-medium hover:bg-primary/5 dark:border-outline-dark">
                                        Edit
                                    </a>

                                    <form action="{{ route('admin.media.categories.destroy', $cat) }}" method="POST"
                                        onsubmit="return confirm('Delete this category? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="rounded-radius border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50 dark:border-red-800/60 dark:text-red-300 dark:hover:bg-red-900/20">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5"
                                class="px-4 py-8 text-center text-on-surface/60 dark:text-on-surface-dark/60">
                                No media categories yet.
                                <a href="{{ route('admin.media.categories.create') }}" class="underline">Create one</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Optional: quick-create (AJAX) --}}
        @if (isset($parents))
            <div x-data="{
                open: false,
                saving: false,
                name: '',
                slug: '',
                parent_id: '',
                description: '',
                parents: @js($parents->map(fn($p) => ['id' => $p->id, 'name' => $p->term?->name])->values()),
                csrf: document.querySelector('meta[name=csrf-token]')?.content || '',
                async save() {
                    this.saving = true;
                    try {
                        const res = await fetch('{{ route('admin.media.categories.quick') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrf
                            },
                            body: JSON.stringify({
                                name: this.name,
                                slug: this.slug || null,
                                parent_id: this.parent_id || null,
                                description: this.description || null
                            })
                        });
                        if (!res.ok) throw new Error('Failed');
                        window.location.reload();
                    } catch (e) {
                        alert('Could not create category. Please try again.');
                    } finally {
                        this.saving = false;
                    }
                }
            }"
                class="rounded-radius border border-outline/70 bg-surface p-4 dark:border-outline-dark/70 dark:bg-surface-dark/40">
                <div class="flex items-center gap-3">
                    <h2 class="text-base font-semibold">Quick Create</h2>
                    <button
                        class="ml-auto rounded-radius border border-outline px-3 py-1.5 text-xs dark:border-outline-dark"
                        @click="open = !open" x-text="open ? 'Close' : 'Open'"></button>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-2" x-show="open" x-cloak>
                    <label class="block text-sm">
                        <span class="mb-1 inline-block">Name</span>
                        <input x-model="name" type="text"
                            class="w-full rounded-radius border border-outline px-3 py-2 dark:border-outline-dark"
                            placeholder="e.g. Banners" />
                    </label>

                    <label class="block text-sm">
                        <span class="mb-1 inline-block">Slug (optional)</span>
                        <input x-model="slug" type="text"
                            class="w-full rounded-radius border border-outline px-3 py-2 dark:border-outline-dark"
                            placeholder="banners" />
                    </label>

                    <label class="block text-sm">
                        <span class="mb-1 inline-block">Parent (optional)</span>
                        <select x-model="parent_id"
                            class="w-full rounded-radius border border-outline px-3 py-2 dark:border-outline-dark">
                            <option value="">— None —</option>
                            <template x-for="p in parents" :key="p.id">
                                <option :value="p.id" x-text="p.name"></option>
                            </template>
                        </select>
                    </label>

                    <label class="block text-sm md:col-span-2">
                        <span class="mb-1 inline-block">Description (optional)</span>
                        <textarea x-model="description" rows="3"
                            class="w-full rounded-radius border border-outline px-3 py-2 dark:border-outline-dark"
                            placeholder="Short description"></textarea>
                    </label>

                    <div class="md:col-span-2 flex items-center justify-end gap-2">
                        <button class="rounded-radius border border-outline px-4 py-2 text-sm dark:border-outline-dark"
                            @click="open=false">Cancel</button>
                        <button
                            class="rounded-radius bg-primary px-4 py-2 text-sm font-medium text-on-primary hover:opacity-90 disabled:opacity-60 dark:bg-primary-dark dark:text-on-primary-dark"
                            :disabled="saving || !name" @click="save()">
                            <span x-show="!saving">Create</span>
                            <span x-show="saving">Creating…</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
