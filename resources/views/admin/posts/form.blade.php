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

                {{-- Content (CKEditor 5 + Media Browser) --}}
                <div class="rounded-radius border border-outline dark:border-outline-dark p-4">
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center gap-3">
                            <label class="block text-sm font-medium">Content</label>
                            <span class="text-xs opacity-70">CKEditor 5 (Classic)</span>
                        </div>
                        <button type="button" id="btn-insert-media" class="text-xs px-3 py-1.5 border rounded-radius">
                            Insert from Media Library
                        </button>
                    </div>

                    <textarea id="content" class="js-ckeditor" name="content" rows="16"
                        data-upload-url="{{ route('admin.ckeditor.upload') }}">{{ old('content', $post->content) }}</textarea>
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
                                <option value="{{ $st }}" @selected(old('status', $post->status ?? 'draft') === $st)>{{ ucfirst($st) }}
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

                {{-- Featured Image (single) --}}
                <div class="rounded-radius border border-outline dark:border-outline-dark p-4 space-y-2">
                    <h3 class="font-semibold text-sm">Featured Image</h3>
                    <x-media-picker name="featured_media_id" :multiple="false" :value="old('featured_media_id', $post->featured_media_id ?? null)" />
                </div>

                {{-- Gallery (multiple) --}}
                <div class="rounded-radius border border-outline dark:border-outline-dark p-4 space-y-2">
                    <h3 class="font-semibold text-sm">Gallery</h3>
                    <x-media-picker name="gallery_ids" :multiple="true" :value="old('gallery_ids', [])" />
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

    {{-- Minimal inline glue: binds Media Browser to the CKEditor instance (no backticks) --}}
    <script>
        (function() {
            function bindInsert(editor) {
                var btn = document.getElementById('btn-insert-media');
                if (!btn) return;

                btn.addEventListener('click', async function() {
                    if (typeof window.openMediaBrowser !== 'function') {
                        alert(
                            'Media Browser is not loaded. Make sure <x-media-browser /> is included in the layout.'
                            );
                        return;
                    }

                    var files = await window.openMediaBrowser({
                        multiple: true
                    });
                    if (!files || !files.length) return;

                    editor.model.change(function(writer) {
                        files.forEach(function(file) {
                            var mime = (file.mime || '').toLowerCase();
                            var isImage = mime.indexOf('image/') === 0;

                            if (isImage) {
                                var img = writer.createElement('imageBlock', {
                                    src: file.url,
                                    alt: file.alt || file.title || ''
                                });
                                editor.model.insertContent(img, editor.model.document
                                    .selection);
                            } else {
                                var html = '<p><a href="' + file.url +
                                    '" target="_blank" rel="noopener">' +
                                    (file.filename || file.url) +
                                    '</a></p>';
                                var viewFrag = editor.data.processor.toView(html);
                                var modelFrag = editor.data.toModel(viewFrag);
                                editor.model.insertContent(modelFrag, editor.model.document
                                    .selection);
                            }
                        });
                    });
                });
            }

            // Bind when your CKEditor boot file dispatches the ready event
            window.addEventListener('ckeditor:ready', function(e) {
                if (e && e.detail && e.detail.editor) bindInsert(e.detail.editor);
            });

            // Fallback if the editor was already created before this script
            var el = document.getElementById('content');
            if (el && el.__editor) bindInsert(el.__editor);

            // Slug auto from title (only when slug is empty)
            var titleEl = document.getElementById('title');
            if (titleEl) {
                titleEl.addEventListener('input', function() {
                    var slugEl = document.getElementById('slug');
                    if (!slugEl) return;
                    if (slugEl.value && slugEl.value.trim().length) return;
                    var v = this.value.toLowerCase()
                        .replace(/[^a-z0-9\s-]/g, '')
                        .replace(/\s+/g, '-')
                        .replace(/-+/g, '-')
                        .substring(0, 96);
                    slugEl.value = v;
                });
            }
        })();
    </script>
@endsection
