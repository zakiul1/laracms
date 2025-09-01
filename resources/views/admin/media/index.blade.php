@extends('admin.layout')

@section('content')
    <div class="space-y-4" x-data="mediaLib()" x-init="init()">

        {{-- top bar: filters --}}
        <div class="flex flex-wrap items-center gap-2">
            <input class="w-64 rounded border px-3 py-2" x-model.debounce.400ms="filters.q" type="search"
                placeholder="Search filename, mime, title…" aria-label="Search media">

            <select class="rounded border px-3 py-2" x-model="filters.term_taxonomy_id" aria-label="Filter by category">
                <option value="">All categories</option>
                <template x-for="cat in categories" :key="cat.id">
                    <option :value="String(cat.id)" x-text="cat.name"></option>
                </template>
            </select>

            <label class="inline-flex items-center gap-2 rounded px-2 py-1 hover:bg-gray-50 dark:hover:bg-neutral-800">
                <input class="rounded" type="checkbox" x-model="filters.images_only">
                <span>Images only</span>
            </label>

            <select class="rounded border px-3 py-2" x-model="filters.sort" aria-label="Sort">
                <option value="newest">Newest</option>
                <option value="oldest">Oldest</option>
                <option value="name">Name (A–Z)</option>
                <option value="size">Size</option>
            </select>

            <button class="ml-auto px-4 py-2 rounded bg-blue-600 text-white" @click="openUpload()">Upload</button>
        </div>

        {{-- grid --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            <template x-for="item in items" :key="item.id">
                <button @click="openInspector(item)"
                    class="group relative aspect-square w-full overflow-hidden rounded border bg-white dark:bg-neutral-900"
                    :class="selectedId === item.id ? 'ring-2 ring-primary' : 'hover:shadow-md'">
                    <img class="h-full w-full object-cover" :src="item.thumb_url || item.url"
                        :alt="item.alt || item.title || item.filename || ''" loading="lazy">
                    <div class="absolute inset-x-0 bottom-0 bg-black/50 text-white text-xs px-2 py-1 truncate"
                        x-text="item.title || item.filename"></div>
                </button>
            </template>
        </div>

        {{-- load more --}}
        <div class="flex justify-center p-4" x-show="!loading && nextPageUrl">
            <button class="px-4 py-2 rounded border" @click="loadMore()">Load more</button>
        </div>

        {{-- empty state --}}
        <div x-show="!loading && !items.length" class="text-center text-sm text-gray-500 py-10">
            No media found.
        </div>

        {{-- loading overlay --}}
        <div x-show="loading" class="text-center text-sm text-gray-500 py-10">Loading…</div>

        {{-- toast --}}
        <div x-cloak x-show="toast.open" class="fixed bottom-4 right-4 rounded px-3 py-2 text-white"
            :class="{
                'bg-green-600': toast.type==='success',
                'bg-red-600': toast.type==='error',
                'bg-slate-700': toast.type==='info'
            }"
            x-text="toast.msg">
        </div>

        {{-- inspector (right drawer) --}}
        <div x-cloak x-show="inspector.open" class="fixed inset-0 z-30" @keydown.escape.window="inspector.open=false">
            <div class="absolute inset-0 bg-black/30" @click.self="inspector.open=false"></div>
            <aside
                class="absolute right-0 top-0 h-full w-full sm:w-[480px] bg-white dark:bg-neutral-900 shadow-xl overflow-y-auto">
                <div class="flex items-center justify-between border-b p-4">
                    <h3 class="font-semibold">Attachment details</h3>
                    <button class="rounded px-2 py-1 hover:bg-gray-100 dark:hover:bg-neutral-800"
                        @click="inspector.open=false">✕</button>
                </div>

                <template x-if="inspector.item">
                    <div class="p-4 space-y-4">
                        <div class="rounded border overflow-hidden">
                            <img class="w-full object-contain max-h-64 bg-black/5"
                                :src="(inspector.item.thumb_url || inspector.item.url)" alt="">
                        </div>

                        <div class="text-xs text-gray-500 space-y-1">
                            <div><strong x-text="inspector.item.mime"></strong></div>
                            <div x-text="'Size: ' + human(inspector.item.size)"></div>
                            <div x-text="'Uploaded: ' + (inspector.item.created_at || '')"></div>
                            <div class="truncate">
                                <span class="font-medium">URL:</span>
                                <a :href="inspector.item.url" class="text-blue-600 underline truncate"
                                    x-text="inspector.item.url" target="_blank" rel="noopener"></a>
                                <button class="ml-2 text-xs rounded border px-2 py-0.5"
                                    @click="navigator.clipboard.writeText(inspector.item.url); showToast('Copied URL','success')">
                                    Copy
                                </button>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="block text-sm font-medium">Title
                                <input type="text" class="mt-1 w-full rounded border px-3 py-2" x-model="editor.name">
                            </label>
                            <label class="block text-sm font-medium">Alt text
                                <input type="text" class="mt-1 w-full rounded border px-3 py-2" x-model="editor.alt">
                            </label>
                            <label class="block text-sm font-medium">Caption
                                <textarea rows="3" class="mt-1 w-full rounded border px-3 py-2" x-model="editor.caption"></textarea>
                            </label>
                            <label class="block text-sm font-medium">Category
                                <select class="mt-1 w-full rounded border px-3 py-2" x-model="editor.term_taxonomy_id">
                                    <option value="">— None —</option>
                                    <template x-for="cat in categories" :key="cat.id">
                                        <option :value="String(cat.id)" x-text="cat.name"></option>
                                    </template>
                                </select>
                            </label>

                            <div class="flex gap-2">
                                <button class="px-4 py-2 rounded bg-blue-600 text-white" @click="saveMeta()">Save</button>
                                <label class="px-4 py-2 rounded border cursor-pointer">
                                    <input type="file" class="hidden" @change="replace">
                                    Replace file
                                </label>
                                <button class="ml-auto px-4 py-2 rounded bg-red-600 text-white"
                                    @click="trash()">Delete</button>
                            </div>
                        </div>
                    </div>
                </template>
            </aside>
        </div>

        {{-- upload modal (compact, scrollable previews, explicit Upload button) --}}
        <div x-cloak x-show="upload.open" @keydown.escape.window="closeUpload()"
            class="fixed inset-0 z-40 flex items-center justify-center">
            <!-- backdrop -->
            <div class="absolute inset-0 bg-black/40" @click.self="closeUpload()"></div>

            <!-- dialog -->
            <div
                class="relative z-10 w-[92vw] max-w-5xl rounded-xl bg-white text-slate-800 shadow-2xl dark:bg-neutral-900 dark:text-slate-100">
                <!-- header -->
                <div class="flex items-center justify-between border-b px-4 py-3 dark:border-white/10">
                    <h3 class="text-base font-semibold">Upload Media</h3>
                    <button type="button" class="rounded p-2 hover:bg-black/5 dark:hover:bg-white/10"
                        @click="closeUpload()" aria-label="Close">✕</button>
                </div>

                <!-- body -->
                <div class="grid gap-6 p-4 md:grid-cols-2">
                    <!-- Left: Dropzone -->
                    <div>
                        <div class="mb-2 text-sm font-medium">Files</div>
                        <div
                            class="filepond-skin max-h-[56vh] overflow-y-auto rounded-lg border border-dashed border-slate-300 p-3 dark:border-white/15">
                            <input type="file" x-ref="pondInput" multiple />
                        </div>
                        <p class="mt-2 text-xs text-red-600" x-text="upload.errors.files"></p>
                    </div>

                    <!-- Right: Options -->
                    <div class="flex flex-col">
                        <div class="mb-4">
                            <label class="mb-1 block text-sm font-medium">Upload to category</label>
                            <select
                                class="w-full rounded border px-3 py-2 text-sm dark:bg-neutral-800 dark:border-white/15"
                                x-model="filters.term_taxonomy_id">
                                <option value="">— Uncategorised —</option>
                                <template x-for="cat in categories" :key="cat.id">
                                    <option :value="String(cat.id)" x-text="cat.name"></option>
                                </template>
                            </select>
                        </div>

                        <div class="mb-4 rounded-lg border p-3 dark:border-white/10">
                            <div class="mb-2 text-sm font-medium">Quick create category</div>
                            <div class="space-y-2">
                                <input type="text"
                                    class="w-full rounded border px-3 py-2 text-sm dark:bg-neutral-800 dark:border-white/15"
                                    placeholder="Category name" x-model="upload.newCategoryName" />
                                <p class="text-xs text-red-600" x-text="upload.errors.newCategoryName"></p>

                                <select
                                    class="w-full rounded border px-3 py-2 text-sm dark:bg-neutral-800 dark:border-white/15"
                                    x-model="upload.newCategoryParent">
                                    <option value="">— Parent (optional) —</option>
                                    <template x-for="cat in categories" :key="'p-' + cat.id">
                                        <option :value="String(cat.id)" x-text="cat.name"></option>
                                    </template>
                                </select>
                                <p class="text-xs text-red-600" x-text="upload.errors.newCategoryParent"></p>

                                <div class="pt-1">
                                    <button type="button"
                                        class="rounded bg-slate-800 px-3 py-1.5 text-sm text-white hover:bg-slate-700 disabled:opacity-60 dark:bg-slate-700 dark:hover:bg-slate-600"
                                        :disabled="upload.creating" @click="createCategory()">
                                        <span x-show="!upload.creating">Create category</span>
                                        <span x-show="upload.creating">Creating…</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-auto flex items-center gap-3">
                            <button type="button"
                                class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:opacity-60"
                                :disabled="upload.uploading" @click="processUpload()">
                                <span x-show="!upload.uploading">Upload</span>
                                <span x-show="upload.uploading">Uploading…</span>
                            </button>

                            <button type="button"
                                class="rounded border px-4 py-2 hover:bg-black/5 dark:hover:bg-white/10"
                                :disabled="upload.uploading" @click="clearUpload()">
                                Clear
                            </button>

                            <button type="button"
                                class="ml-auto rounded px-4 py-2 hover:bg-black/5 dark:hover:bg-white/10"
                                :disabled="upload.uploading" @click="closeUpload()">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- modal-specific CSS tweaks for FilePond --}}
        <style>
            .filepond-skin .filepond--root {
                max-height: 56vh;
            }

            .filepond-skin .filepond--item {
                height: 110px;
            }

            .filepond-skin .filepond--panel-root {
                border-radius: 0.5rem;
            }

            .filepond-skin .filepond--drop-label {
                padding: 0.6rem 0.8rem;
            }
        </style>

    </div>
@endsection
