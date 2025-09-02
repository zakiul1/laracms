@extends('admin.layout')

@section('head')
    {{-- Ensure CSRF meta is present for fetch calls --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Expose routes to the JS module --}}
    <script>
        window.MediaRoutes = {
            list: '{{ route('admin.media.list') }}',
            upload: '{{ route('admin.media.upload') }}',
            meta: '{{ route('admin.media.meta', ':id') }}',
            replace: '{{ route('admin.media.replace', ':id') }}',
            destroy: '{{ route('admin.media.destroy', ':id') }}',
            bulkDelete: '{{ route('admin.media.bulk-delete') }}',
            bulkRestore: '{{ route('admin.media.bulk-restore') }}',
            bulkForce: '{{ route('admin.media.bulk-force') }}',
            catsJson: '{{ route('admin.media.categories.json') }}',
            quickCat: '{{ route('admin.media.categories.quick') }}',
            csrf: '{{ csrf_token() }}',
        };
    </script>
@endsection

@section('content')
    <div class="space-y-6" x-data="mediaLib()" x-init="init()">

        {{-- ===== Top uploader ===== --}}
        <div class="rounded border bg-white dark:bg-neutral-900">
            <div class="flex flex-wrap items-end gap-3 p-4 border-b">
                <label class="block">
                    <span class="block text-sm font-medium mb-1">Upload to category <span class="text-red-600">*</span></span>
                    <select class="rounded border px-3 py-2 min-w-[240px]" x-model="uploader.categoryId">
                        <option value="">— Select category —</option>
                        <template x-for="cat in categories" :key="cat.id">
                            <option :value="String(cat.id)" x-text="cat.name"></option>
                        </template>
                    </select>
                </label>

                <div class="flex items-end gap-2">
                    <label class="block">
                        <span class="block text-sm font-medium mb-1">New category</span>
                        <input class="rounded border px-3 py-2 min-w-[220px]" placeholder="e.g. Banners"
                            x-model="uploader.newCatName">
                    </label>
                    <label class="block">
                        <span class="block text-sm font-medium mb-1">Parent</span>
                        <select class="rounded border px-3 py-2 min-w-[200px]" x-model="uploader.newCatParent">
                            <option value="">— none —</option>
                            <template x-for="cat in categories" :key="'p' + cat.id">
                                <option :value="String(cat.id)" x-text="cat.name"></option>
                            </template>
                        </select>
                    </label>
                    <button class="px-3 py-2 rounded border" :disabled="uploader.creatingCat"
                        @click="createCategoryInline()">
                        <span x-show="!uploader.creatingCat">Add</span>
                        <span x-show="uploader.creatingCat">Adding…</span>
                    </button>
                </div>

                <p class="text-sm text-red-600" x-text="uploader.newCatErr"></p>
            </div>

            {{-- Dropzone --}}
            <div class="p-4">
                <div class="relative flex flex-col items-center justify-center text-center border-2 border-dashed rounded-md px-6
                        h-40 md:h-48 bg-slate-50/50 dark:bg-neutral-800/40 transition"
                    :class="uploader.dragOver ? 'border-blue-500 bg-blue-50/70' : 'border-slate-300 dark:border-neutral-700'"
                    @dragover="onDragOver" @dragleave="onDragLeave" @drop="onDrop">

                    <template x-if="!uploader.categoryId">
                        <div
                            class="absolute inset-0 grid place-content-center rounded-md bg-red-50/80 text-red-700 text-sm font-medium">
                            Select a category to enable uploads.
                        </div>
                    </template>

                    <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mb-2 opacity-60" viewBox="0 0 24 24"
                        fill="currentColor" aria-hidden="true">
                        <path
                            d="M19.5 14.25v-2.378a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.372V6.75A2.25 2.25 0 0 0 11.25 4.5h-6a2.25 2.25 0 0 0-2.25 2.25v9A2.25 2.25 0 0 0 5.25 18h5.878M15 10.5l-3 3 3 3m-3-3h12.75" />
                    </svg>

                    <p class="font-medium">Drop files to upload</p>
                    <p class="text-xs text-gray-500">or</p>
                    <button type="button" class="mt-2 px-3 py-1.5 rounded border bg-white dark:bg-neutral-900"
                        @click="openFilePicker()">Select Files</button>

                    <input type="file" multiple class="hidden" x-ref="fileInput" @change="onFileChange">
                    <p class="mt-2 text-xs text-gray-500">Allowed: images, MP4/WebM, PDF. Max 20MB/file.</p>
                </div>

                {{-- Queue preview --}}
                <div x-show="uploader.queue.length" class="mt-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="text-sm">
                            <span x-text="uploader.uploadedCount"></span>/<span x-text="uploader.queue.length"></span>
                            file(s) uploaded — <span x-text="overallProgress"></span>%
                        </div>
                        <div class="flex gap-2">
                            <button class="px-3 py-1.5 rounded border" @click="clearQueue()">Clear</button>
                            <button class="px-3 py-1.5 rounded bg-blue-600 text-white" :disabled="uploader.uploading"
                                @click="startUpload()">Start upload</button>
                        </div>
                    </div>

                    <div class="h-1 bg-slate-200 dark:bg-neutral-700 rounded">
                        <div class="h-1 rounded bg-blue-600 transition-all" :style="`width:${overallProgress}%`"></div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                        <template x-for="(f, idx) in uploader.queue" :key="idx">
                            <div class="relative rounded border bg-white dark:bg-neutral-900 overflow-hidden">
                                <button class="absolute right-1 top-1 z-10 rounded bg-black/50 text-white px-1"
                                    @click="removeFromQueue(idx)">×</button>

                                <template x-if="f.url">
                                    <img :src="f.url" class="aspect-square w-full object-cover"
                                        :alt="f.name">
                                </template>
                                <template x-if="!f.url">
                                    <div class="aspect-square grid place-content-center text-xs text-gray-500">
                                        <div class="text-center px-2">
                                            <div class="mb-1">No preview</div>
                                            <div class="truncate" x-text="f.type || 'file'"></div>
                                        </div>
                                    </div>
                                </template>

                                <div class="p-2 text-xs space-y-1">
                                    <div class="truncate" x-text="f.name"></div>
                                    <div class="text-gray-500" x-text="human(f.size)"></div>
                                    <template x-if="f.status === 'uploading'">
                                        <div class="w-full h-1 bg-slate-200 dark:bg-neutral-700 rounded overflow-hidden">
                                            <div class="h-1 bg-blue-600" :style="`width:${f.progress}%`"></div>
                                        </div>
                                    </template>
                                    <template x-if="f.status === 'error'">
                                        <p class="text-red-600" x-text="f.error"></p>
                                    </template>
                                    <template x-if="f.status === 'skipped'">
                                        <p class="text-amber-600" x-text="f.error"></p>
                                    </template>
                                    <template x-if="f.status === 'done'">
                                        <p class="text-green-600">Uploaded</p>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== FILTERS ===== --}}
        <div class="flex flex-wrap items-center gap-2">
            <input class="w-64 rounded border px-3 py-2" x-model.debounce.400ms="filters.q" type="search"
                placeholder="Search filename, mime, title…" aria-label="Search media">

            <select class="rounded border px-3 py-2" x-model="filters.term_taxonomy_id" aria-label="Filter by category">
                <option value="">All categories</option>
                <template x-for="cat in categories" :key="'f' + cat.id">
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
                <option value="name">Name</option>
                <option value="largest">Largest</option>
                <option value="smallest">Smallest</option>
            </select>

            <div class="ml-auto flex items-center gap-2">
                <label class="text-sm text-gray-600">Per page</label>
                <select class="rounded border px-2 py-1" x-model.number="pagination.perPage" @change="goto(1)">
                    <option :value="20">20</option>
                    <option :value="40">40</option>
                    <option :value="60">60</option>
                    <option :value="100">100</option>
                </select>
            </div>
        </div>

        {{-- ===== BULK BAR ===== --}}
        <div class="flex items-center gap-2" x-show="selected.size">
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" @change="toggleSelectAll($event)">
                <span class="text-sm">Select all on page</span>
            </label>
            <span class="text-sm text-gray-600" x-text="`${selected.size} selected`"></span>
            <button class="px-3 py-1.5 rounded border" @click="bulkTrash()">Move to Trash</button>
            <button class="px-3 py-1.5 rounded border" @click="bulkRestore()">Restore</button>
            <button class="px-3 py-1.5 rounded bg-red-600 text-white" @click="bulkForce()">Delete permanently</button>
        </div>

        {{-- ===== GRID ===== --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            <template x-for="item in items" :key="item.id">
                <div
                    class="group relative aspect-square w-full overflow-hidden rounded border bg-white dark:bg-neutral-900">
                    <input type="checkbox" class="absolute left-1 top-1 z-10 w-4 h-4" :checked="selected.has(item.id)"
                        @click.stop @change="toggleSelect(item.id, $event.target.checked)">
                    <button @click="openInspector(item)" class="absolute inset-0"
                        :class="selected.has(item.id) ? 'ring-2 ring-blue-600 ring-offset-1' : 'hover:shadow-md'">
                        <img class="h-full w-full object-cover" :src="thumb(item)"
                            :alt="item.alt || item.title || item.filename || ''" loading="lazy">
                    </button>
                    <div class="absolute inset-x-0 bottom-0 bg-black/50 text-white text-xs px-2 py-1 truncate"
                        x-text="item.title || item.filename"></div>
                </div>
            </template>
        </div>

        {{-- ===== PAGINATION ===== --}}
        <div class="flex items-center justify-between py-3">
            <div class="text-sm text-gray-600"
                x-text="`Page ${pagination.page} of ${pagination.lastPage} • ${pagination.total} items`"></div>
            <div class="flex items-center gap-2">
                <button class="px-3 py-1.5 rounded border" :disabled="pagination.page <= 1"
                    @click="goto(pagination.page - 1)">Prev</button>
                <button class="px-3 py-1.5 rounded border" :disabled="pagination.page >= pagination.lastPage"
                    @click="goto(pagination.page + 1)">Next</button>
            </div>
        </div>

        {{-- EMPTY / LOADING --}}
        <div x-show="!loading && !items.length" class="text-center text-sm text-gray-500 py-10">No media found.</div>
        <div x-show="loading" class="text-center text-sm text-gray-500 py-10">Loading…</div>

        {{-- TOAST --}}
        <div x-cloak x-show="toast.open" class="fixed bottom-4 right-4 rounded px-3 py-2 text-white"
            :class="{
                'bg-green-600': toast.type==='success',
                'bg-red-600': toast.type==='error',
                'bg-slate-700': toast.type==='info'
            }"
            x-text="toast.msg"></div>

        {{-- ===== INSPECTOR ===== --}}
        <div x-cloak x-show="inspector.open" class="fixed inset-0 z-30" @keydown.escape.window="closeInspector()">
            <div class="absolute inset-0 bg-black/30" @click.self="closeInspector()"></div>
            <aside
                class="absolute right-0 top-0 h-full w-full sm:w-[480px] bg-white dark:bg-neutral-900 shadow-xl overflow-y-auto">
                <div class="flex items-center justify-between border-b p-4">
                    <h3 class="font-semibold">Attachment details</h3>
                    <button class="rounded px-2 py-1 hover:bg-gray-100 dark:hover:bg-neutral-800"
                        @click="closeInspector()">✕</button>
                </div>

                <template x-if="inspector.item">
                    <div class="p-4 space-y-4">
                        <div class="rounded border overflow-hidden">
                            <img class="w-full object-contain max-h-64 bg-black/5" :src="thumb(inspector.item)"
                                alt="">
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
                                    @click="copyUrl(inspector.item.url)">Copy</button>
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
                                    <template x-for="cat in categories" :key="'e' + cat.id">
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
    </div>
@endsection
