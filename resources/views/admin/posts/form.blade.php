@php
    $isPage = $type === 'page';
    $saveRoute = $post->exists
        ? route($isPage ? 'admin.pages.update' : 'admin.posts.update', $post)
        : route($isPage ? 'admin.pages.store' : 'admin.posts.store');
@endphp

<form x-data="{ action: 'save' }" method="POST" action="{{ $saveRoute }}" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    @csrf
    @if ($post->exists)
        @method('PATCH')
    @endif

    <input type="hidden" name="action" :value="action">

    {{-- LEFT: Editor & SEO --}}
    <div class="lg:col-span-2 space-y-4">
        {{-- Title --}}
        <div class="rounded-radius border border-outline dark:border-outline-dark p-3">
            <label class="block text-sm mb-1">Title</label>
            <input type="text" name="title" value="{{ old('title', $post->title) }}" required
                class="w-full border border-outline rounded-radius bg-surface px-2 py-2 dark:border-outline-dark dark:bg-surface-dark/50">
            @error('title')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Permalink / Slug --}}
        <div class="rounded-radius border border-outline dark:border-outline-dark p-3">
            <label class="block text-sm mb-1">Permalink</label>
            <div class="flex items-center gap-2">
                <span class="text-xs opacity-70">{{ url('/') }}/</span>
                <input type="text" name="slug" value="{{ old('slug', $post->slug) }}"
                    class="flex-1 border border-outline rounded-radius bg-surface px-2 py-1.5 dark:border-outline-dark dark:bg-surface-dark/50">
            </div>
            @error('slug')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Content --}}
        <div class="rounded-radius border border-outline dark:border-outline-dark p-3">
            <label class="block text-sm mb-1">Content</label>
            <textarea name="content" rows="14"
                class="w-full border border-outline rounded-radius bg-surface px-2 py-2 dark:border-outline-dark dark:bg-surface-dark/50 editor">{{ old('content', $post->content) }}</textarea>
            @error('content')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Excerpt --}}
        <div class="rounded-radius border border-outline dark:border-outline-dark p-3">
            <label class="block text-sm mb-1">Excerpt / Summary</label>
            <textarea name="excerpt" rows="3"
                class="w-full border border-outline rounded-radius bg-surface px-2 py-2 dark:border-outline-dark dark:bg-surface-dark/50">{{ old('excerpt', $post->excerpt) }}</textarea>
            @error('excerpt')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Template (Pages only) --}}
        @if ($isPage)
            <div class="rounded-radius border border-outline dark:border-outline-dark p-3">
                <label class="block text-sm mb-1">Template</label>
                <select name="template"
                    class="w-full border border-outline rounded-radius bg-surface px-2 py-2 dark:border-outline-dark dark:bg-surface-dark/50">
                    <option value="">Default</option>
                    @foreach ($templates ?? [] as $value => $label)
                        <option value="{{ $value }}" @selected(old('template', $post->template) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('template')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        @endif

        {{-- SEO Settings --}}
        <div class="rounded-radius border border-outline dark:border-outline-dark p-3">
            <h3 class="font-semibold mb-2">SEO Settings</h3>
            @php $seo = old('seo', optional($post->seo)->toArray() ?? []); @endphp

            {{-- Row: Meta Title & Meta Keywords (2 cols) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs mb-1">Meta Title</label>
                    <input name="seo[meta_title]" value="{{ $seo['meta_title'] ?? '' }}"
                        class="w-full border border-outline rounded-radius bg-surface px-2 py-1.5 dark:border-outline-dark dark:bg-surface-dark/50">
                </div>
                <div>
                    <label class="block text-xs mb-1">Meta Keywords</label>
                    <input name="seo[meta_keywords]" value="{{ $seo['meta_keywords'] ?? '' }}"
                        class="w-full border border-outline rounded-radius bg-surface px-2 py-1.5 dark:border-outline-dark dark:bg-surface-dark/50">
                </div>
            </div>

            {{-- Row: Meta Description --}}
            <div class="mt-3">
                <label class="block text-xs mb-1">Meta Description</label>
                <textarea name="seo[meta_description]" rows="3"
                    class="w-full border border-outline rounded-radius bg-surface px-2 py-1.5 dark:border-outline-dark dark:bg-surface-dark/50">{{ $seo['meta_description'] ?? '' }}</textarea>
            </div>

            {{-- Row: Robots --}}
            <div class="mt-3">
                <label class="block text-xs mb-1">Robots</label>
                <div class="flex items-center gap-4">
                    <label class="inline-flex items-center gap-2 text-xs">
                        <input type="checkbox" name="seo[robots_index]" value="1" @checked($seo['robots_index'] ?? true)>
                        Index
                    </label>
                    <label class="inline-flex items-center gap-2 text-xs">
                        <input type="checkbox" name="seo[robots_follow]" value="1" @checked($seo['robots_follow'] ?? true)>
                        Follow
                    </label>
                </div>
            </div>
        </div>
    </div>

    {{-- RIGHT: Sidebar panels --}}
    <aside class="space-y-4">
        {{-- Publish --}}
        <div class="rounded-radius border border-outline dark:border-outline-dark p-3">
            <h3 class="font-semibold mb-2">Publish</h3>

            <div class="grid grid-cols-1 gap-3">
                <div>
                    <label class="block text-xs mb-1">Status</label>
                    <select name="status"
                        class="w-full border border-outline rounded-radius bg-surface px-2 py-2 dark:border-outline-dark dark:bg-surface-dark/50">
                        <option value="draft" @selected(old('status', $post->status) === 'draft')>Draft</option>
                        <option value="published" @selected(old('status', $post->status) === 'published')>Published</option>
                    </select>
                </div>

                {{-- Visibility + password toggle --}}
                <div x-data="{ v: '{{ old('visibility', $post->visibility) }}' }">
                    <label class="block text-xs mb-1">Visibility</label>
                    <select name="visibility" x-model="v"
                        class="w-full border border-outline rounded-radius bg-surface px-2 py-2 dark:border-outline-dark dark:bg-surface-dark/50">
                        <option value="public">Public</option>
                        <option value="private">Private</option>
                        <option value="password">Password</option>
                    </select>

                    <div class="mt-2" x-cloak x-show="v === 'password'">
                        <label class="block text-xs mb-1">Password</label>
                        <input type="text" name="password" value="{{ old('password', $post->password) }}"
                            class="w-full border border-outline rounded-radius bg-surface px-2 py-1.5 dark:border-outline-dark dark:bg-surface-dark/50">
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 mt-3">
                <button type="submit" @click="action='save'"
                    class="px-3 py-1.5 rounded-radius bg-surface-alt border border-outline text-sm hover:bg-surface dark:bg-surface-dark-alt dark:border-outline-dark">
                    Save Draft
                </button>
                <button type="submit" @click="action='publish'"
                    class="px-3 py-1.5 rounded-radius bg-primary text-white text-sm">
                    Publish
                </button>
            </div>
        </div>

        {{-- Featured Images (multiple via your component) --}}
        <div class="rounded-radius border border-outline dark:border-outline-dark p-3">
            {{--   <h3 class="font-semibold mb-2">Featured Images</h3> --}}
            <x-media-picker name="gallery" :multiple="true" :value="old('gallery', $post->gallery->pluck('id')->all())" />
        </div>

        {{-- Categories (POSTS only) --}}
        @unless ($isPage)
            <div class="rounded-radius border border-outline dark:border-outline-dark p-3">
                <h3 class="font-semibold mb-2">Categories</h3>
                @php
                    $selectedCats = collect(old('categories', $selectedCategoryIds ?? []))
                        ->map(fn($v) => (int) $v)
                        ->all();
                @endphp
                <div class="max-h-48 overflow-auto space-y-1">
                    @foreach ($categoriesTree ?? [] as $cat)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="categories[]" value="{{ $cat['id'] }}"
                                @checked(in_array($cat['id'], $selectedCats, true))>
                            <span style="padding-left: {{ $cat['depth'] * 12 }}px">{{ $cat['name'] }}</span>
                        </label>
                    @endforeach
                </div>

                {{-- Quick add with Parent selector --}}
                {{-- Quick add with Parent selector (fixed layout) --}}
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <input type="text" id="new-cat"
                        class="flex-1 min-w-[10rem] border border-outline rounded-radius px-2 py-1 text-sm"
                        placeholder="New category name…" />

                    <select id="new-cat-parent"
                        class="min-w-[12rem] border border-outline rounded-radius px-2 py-1 text-sm">
                        <option value="0">— Parent: none —</option>
                        @foreach ($categoriesTree ?? [] as $cat)
                            <option value="{{ $cat['id'] }}">
                                {{ str_repeat('— ', $cat['depth']) . $cat['name'] }}
                            </option>
                        @endforeach
                    </select>

                    <button type="button" class="shrink-0 text-sm px-3 py-1 border rounded-radius cursor-pointer"
                        @click="
            fetch('{{ route('admin.taxonomies.category.quick') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    name: document.getElementById('new-cat').value,
                    parent: Number(document.getElementById('new-cat-parent').value) || 0
                })
            })
            .then(r => r.ok ? r.json() : r.json().then(err => Promise.reject(err)))
            .then(() => location.reload())
            .catch(() => alert('Could not create category'));
        ">
                        Add
                    </button>
                </div>

            </div>
        @endunless

        {{-- Tags --}}
        <div class="rounded-radius border border-outline dark:border-outline-dark p-3">
            <h3 class="font-semibold mb-2">Tags</h3>
            <input type="text" name="tags[]"
                class="w-full border border-outline rounded-radius px-2 py-1.5 text-sm"
                placeholder="Comma separated or add one by one">
        </div>

        {{-- Custom Fields --}}
        <div class="rounded-radius border border-outline dark:border-outline-dark p-3" x-data="{ rows: @js(old('meta', $post->metas->map(fn($m) => ['key' => $m->meta_key, 'value' => $m->meta_value])->values()->all())) }">
            <h3 class="font-semibold mb-2">Custom Fields</h3>

            <template x-if="!rows.length">
                <p class="text-xs opacity-70">No custom fields.</p>
            </template>

            <template x-for="(row,idx) in rows" :key="idx">
                <div class="flex items-center gap-2 mb-2">
                    <input class="w-1/3 border border-outline rounded-radius px-2 py-1.5 text-xs" placeholder="key"
                        :name="'meta[' + idx + '][key]'" x-model="row.key">
                    <input class="flex-1 border border-outline rounded-radius px-2 py-1.5 text-xs" placeholder="value"
                        :name="'meta[' + idx + '][value]'" x-model="row.value">
                    <button type="button" class="text-red-600 text-xs" @click="rows.splice(idx,1)">×</button>
                </div>
            </template>

            <button type="button" class="text-xs px-2 py-1 border rounded-radius"
                @click="rows.push({key:'',value:''})">
                Add Field
            </button>
        </div>
    </aside>
</form>

@push('head')
    {{-- If you use CKEditor/Tiptap/Quill, initialize it via your app.js --}}
@endpush
