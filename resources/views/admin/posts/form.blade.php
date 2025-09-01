@extends('admin.layout', ['title' => $mode === 'create' ? 'New Post' : 'Edit Post'])

@section('content')
    <form method="POST" action="{{ $mode === 'create' ? route('admin.posts.store') : route('admin.posts.update', $post) }}"
        enctype="multipart/form-data">
        @csrf
        @if ($mode === 'edit')
            @method('PATCH')
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- MAIN --}}
            <div class="lg:col-span-2 space-y-4">

                {{-- Title --}}
                <div class="rounded-radius border border-outline dark:border-outline-dark p-4">
                    <label class="block text-sm font-medium mb-1">Title</label>
                    <input id="title" name="title" type="text" class="w-full rounded-radius border px-3 py-2"
                        value="{{ old('title', $post->title) }}" required>
                    @error('title')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Slug --}}
                <div class="rounded-radius border border-outline dark:border-outline-dark p-4">
                    <label class="block text-sm font-medium mb-1">Slug (permalink)</label>
                    <input id="slug" name="slug" type="text" class="w-full rounded-radius border px-3 py-2"
                        placeholder="auto-from-title if empty" value="{{ old('slug', $post->slug) }}">
                    <p class="text-xs opacity-70 mt-1">Auto-generated from Title if left empty.</p>
                    @error('slug')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Content (CKEditor 5) --}}
                <div class="rounded-radius border border-outline dark:border-outline-dark p-4">
                    <div class="flex items-center justify-between">
                        <label class="block text-sm font-medium mb-1">Content</label>
                        <span class="text-xs opacity-70">CKEditor 5 (Classic)</span>
                    </div>
                    <textarea id="content" name="content" rows="16">{{ old('content', $post->content) }}</textarea>
                    @error('content')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Excerpt --}}
                <div class="rounded-radius border border-outline dark:border-outline-dark p-4">
                    <label class="block text-sm font-medium mb-1">Excerpt</label>
                    <textarea name="excerpt" rows="3" class="w-full rounded-radius border px-3 py-2">{{ old('excerpt', $post->excerpt) }}</textarea>
                    @error('excerpt')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Revisions --}}
                @if ($mode === 'edit' && $post->revisions()->exists())
                    <div class="rounded-radius border border-outline dark:border-outline-dark p-4">
                        <h3 class="font-semibold mb-2 text-sm">Revisions</h3>
                        <ul class="text-xs space-y-1">
                            @foreach ($post->revisions()->latest()->limit(10)->get() as $rev)
                                <li>{{ $rev->created_at->format('Y-m-d H:i') }} by #{{ $rev->user_id ?? 'system' }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            {{-- SIDEBAR --}}
            <div class="space-y-4">

                {{-- Publish --}}
                <div class="rounded-radius border border-outline dark:border-outline-dark p-4 space-y-3">
                    <h3 class="font-semibold text-sm">Publish</h3>

                    <div>
                        <label class="block text-xs opacity-70">Status</label>
                        <select name="status" class="w-full rounded-radius border px-2 py-1.5">
                            @foreach (['draft', 'published'] as $st)
                                <option value="{{ $st }}" @selected(old('status', $post->status ?? 'draft') === $st)>
                                    {{ ucfirst($st) }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs opacity-70">Publish at</label>
                        <input type="datetime-local" name="published_at" class="w-full rounded-radius border px-2 py-1.5"
                            value="{{ old('published_at', optional($post->published_at)->format('Y-m-d\TH:i')) }}">
                        @error('published_at')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Author --}}
                <div class="rounded-radius border border-outline dark:border-outline-dark p-4 space-y-3">
                    <h3 class="font-semibold text-sm">Author</h3>
                    <select name="author_id" class="w-full rounded-radius border px-2 py-1.5">
                        <option value="">(current)</option>
                        @foreach (\App\Models\User::orderBy('name')->limit(200)->get() as $u)
                            <option value="{{ $u->id }}" @selected(old('author_id', $post->author_id) === $u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                    @error('author_id')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Featured & Gallery UI (kept as-is; JS uploads are optional) --}}
                <div class="rounded-radius border border-outline dark:border-outline-dark p-4 space-y-3"
                    x-data="imageBox()">
                    <h3 class="font-semibold text-sm">Featured Image & Gallery</h3>

                    <div class="space-y-2">
                        <label class="block text-xs opacity-70 mb-1">Featured image</label>
                        <input type="file" accept="image/*" @change="previewFeatured($event)"
                            class="block w-full text-sm">
                        <div class="mt-2" x-show="featuredPreview">
                            <img :src="featuredPreview" class="w-24 h-24 object-cover rounded-md border" alt="">
                        </div>
                        <input type="hidden" name="featured_media_id" x-model="featuredId">
                        <button type="button" class="mt-2 px-2 py-1.5 border rounded-radius text-sm"
                            @click="uploadFeatured()">Upload featured</button>
                    </div>

                    <hr class="my-2 border-outline dark:border-outline-dark">

                    <div class="space-y-2">
                        <label class="block text-xs opacity-70 mb-1">Gallery (multiple)</label>
                        <input type="file" accept="image/*" multiple @change="previewGallery($event)"
                            class="block w-full text-sm">
                        <div class="flex flex-wrap gap-2 mt-2">
                            <template x-for="(src,i) in galleryPreviews" :key="i">
                                <img :src="src" class="w-16 h-16 object-cover rounded-md border">
                            </template>
                        </div>

                        <div class="mt-2">
                            <button type="button" class="px-2 py-1.5 border rounded-radius text-sm"
                                @click="uploadGallery()">Upload gallery</button>
                        </div>

                        <template x-for="id in galleryIds" :key="id">
                            <input type="hidden" name="gallery_ids[]" :value="id">
                        </template>
                    </div>
                </div>

                {{-- Categories --}}
                <div class="rounded-radius border border-outline dark:border-outline-dark p-4 space-y-2">
                    <h3 class="font-semibold text-sm">Categories</h3>
                    @php
                        $catTax = \App\Models\Taxonomy::where('slug', 'category')->first();
                        $selectedCats = old(
                            'category_ids',
                            $post
                                ->terms()
                                ->whereHas('taxonomy', fn($q) => $q->where('slug', 'category'))
                                ->pluck('terms.id')
                                ->toArray(),
                        );
                    @endphp
                    <div class="max-h-48 overflow-y-auto space-y-1">
                        @if ($catTax)
                            @foreach ($catTax->terms()->orderBy('name')->get() as $term)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="category_ids[]" value="{{ $term->id }}"
                                        @checked(in_array($term->id, $selectedCats))>
                                    <span>{{ $term->name }}</span>
                                </label>
                            @endforeach
                        @else
                            <p class="text-xs opacity-70">
                                No categories yet.
                                <a class="underline" href="{{ route('admin.categories.index') }}">Create one</a>.
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Tags --}}
                <div class="rounded-radius border border-outline dark:border-outline-dark p-4 space-y-2">
                    <h3 class="font-semibold text-sm">Tags</h3>
                    <input name="tags" type="text" class="w-full rounded-radius border px-2 py-1.5"
                        placeholder="comma,separated,tags"
                        value="{{ old('tags', $post->terms()->whereHas('taxonomy', fn($q) => $q->where('slug', 'post_tag'))->pluck('name')->implode(', ')) }}">
                </div>

                <div class="flex justify-end">
                    <button class="px-4 py-2 bg-primary text-white rounded-radius">
                        {{ $mode === 'create' ? 'Create' : 'Update' }}
                    </button>
                </div>
            </div>
        </div>
    </form>

    {{-- CKEditor 5 Classic (CDN) --}}
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
    <script>
        ClassicEditor
            .create(document.querySelector('#content'), {
                toolbar: {
                    items: [
                        'undo', 'redo', '|',
                        'heading', '|',
                        'bold', 'italic', 'underline', 'link', '|',
                        'bulletedList', 'numberedList', 'blockQuote', '|',
                        'insertTable', 'imageUpload', 'mediaEmbed', '|',
                        'codeBlock'
                    ]
                },
                heading: {
                    options: [{
                            model: 'paragraph',
                            title: 'Paragraph',
                            class: 'ck-heading_paragraph'
                        },
                        {
                            model: 'heading2',
                            view: 'h2',
                            title: 'Heading 2',
                            class: 'ck-heading_heading2'
                        },
                        {
                            model: 'heading3',
                            view: 'h3',
                            title: 'Heading 3',
                            class: 'ck-heading_heading3'
                        },
                        {
                            model: 'heading4',
                            view: 'h4',
                            title: 'Heading 4',
                            class: 'ck-heading_heading4'
                        },
                    ]
                },
                mediaEmbed: {
                    previewsInData: true
                },
                simpleUpload: {
                    uploadUrl: "{{ route('admin.ckeditor.upload') }}",
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                }
            })
            .catch(console.error);
    </script>

    {{-- Slug auto from title --}}
    <script>
        document.getElementById('title')?.addEventListener('input', function() {
            const slugEl = document.getElementById('slug');
            if (!slugEl) return;
            if (slugEl.value.trim().length) return; // don't overwrite manual edits
            slugEl.value = this.value.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .substring(0, 96);
        });
    </script>

    {{-- Your imageBox() Alpine helper can stay as-is (optional uploads UI) --}}
@endsection
