<aside class="w-64 shrink-0 border-r bg-white">
    <nav class="p-3 space-y-1">

        {{-- Dashboard (no submenu) --}}
        <a href="{{ route('admin.dashboard') }}"
            class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100
              {{ request()->routeIs('admin.dashboard') ? 'bg-gray-100 font-medium' : '' }}">
            <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
            <span>Dashboard</span>
        </a>

        {{-- Posts (with submenu; top-level has icon, submenu has NO icons) --}}
        @php $postsOpen = request()->routeIs('admin.posts.*') || request()->routeIs('admin.categories.*'); @endphp
        <div x-data="{ isExpanded: {{ $postsOpen ? 'true' : 'false' }} }" class="rounded">
            <button type="button" @click="isExpanded = !isExpanded" id="grp-posts-btn"
                class="w-full flex items-center justify-between px-3 py-2 rounded hover:bg-gray-100">
                <span class="flex items-center gap-2">
                    <i data-lucide="file-text" class="w-5 h-5"></i>
                    <span>Posts</span>
                </span>
                <svg :class="isExpanded ? 'rotate-180' : ''" class="w-4 h-4 transition-transform" viewBox="0 0 20 20"
                    fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 011.08 1.04l-4.25 4.25a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z"
                        clip-rule="evenodd" />
                </svg>
            </button>

            <ul x-cloak x-collapse x-show="isExpanded" id="grp-posts" aria-labelledby="grp-posts-btn"
                class="mt-1 pl-8 space-y-1 submenu">
                <li>
                    <a href="{{ route('admin.posts.index') }}"
                        class="block px-3 py-1.5 rounded hover:bg-gray-100
                    {{ request()->routeIs('admin.posts.index') ? 'bg-gray-100 font-medium' : '' }}">
                        All Posts
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.posts.create') }}"
                        class="block px-3 py-1.5 rounded hover:bg-gray-100
                    {{ request()->routeIs('admin.posts.create') ? 'bg-gray-100 font-medium' : '' }}">
                        Create Post
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.categories.index') }}"
                        class="block px-3 py-1.5 rounded hover:bg-gray-100
                    {{ request()->routeIs('admin.categories.*') ? 'bg-gray-100 font-medium' : '' }}">
                        Categories
                    </a>
                </li>
            </ul>
        </div>

        {{-- Media (with submenu) --}}
        @php $mediaOpen = request()->routeIs('admin.media.*'); @endphp
        <div x-data="{ isExpanded: {{ $mediaOpen ? 'true' : 'false' }} }" class="rounded">
            <button type="button" @click="isExpanded = !isExpanded" id="grp-media-btn"
                class="w-full flex items-center justify-between px-3 py-2 rounded hover:bg-gray-100">
                <span class="flex items-center gap-2">
                    <i data-lucide="images" class="w-5 h-5"></i>
                    <span>Media</span>
                </span>
                <svg :class="isExpanded ? 'rotate-180' : ''" class="w-4 h-4 transition-transform" viewBox="0 0 20 20"
                    fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 011.08 1.04l-4.25 4.25a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z"
                        clip-rule="evenodd" />
                </svg>
            </button>

            <ul x-cloak x-collapse x-show="isExpanded" id="grp-media" aria-labelledby="grp-media-btn"
                class="mt-1 pl-8 space-y-1 submenu">
                <li>
                    <a href="{{ route('admin.media.index') }}"
                        class="block px-3 py-1.5 rounded hover:bg-gray-100
                    {{ request()->routeIs('admin.media.index') ? 'bg-gray-100 font-medium' : '' }}">
                        Library
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.media.upload') }}"
                        class="block px-3 py-1.5 rounded hover:bg-gray-100
                    {{ request()->routeIs('admin.media.upload') ? 'bg-gray-100 font-medium' : '' }}">
                        Upload
                    </a>
                </li>
            </ul>
        </div>

        {{-- Add more groups following the same pattern... --}}

    </nav>
</aside>

{{-- Safety net: Never show icons inside submenu items, even if someone adds them --}}
<style>
    .submenu [data-lucide],
    .submenu>a svg {
        display: none !important;
    }
</style>
