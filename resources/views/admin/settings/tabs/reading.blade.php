@php
    $v = $values ?? [];

    // Selected values with sane defaults
    $type = $v['home_type'] ?? 'posts';
    $postsPerPage = $v['posts_per_page'] ?? 10;
    $homePageId = $v['home_page_id'] ?? null;
    $blogPageId = $v['blog_page_id'] ?? null;
    $feedItems = $v['feed_items'] ?? 10;
    $feedShow = $v['feed_show'] ?? 'full';
    $discourage = !empty($v['discourage_indexing']);

    // Build a unified pages map: [id => title]
    $pagesMap = [];
    if (isset($pages) && is_array($pages)) {
        // controller provided assoc: [id => title]
        $pagesMap = $pages;
    } elseif (isset($pagesForSelect) && is_array($pagesForSelect)) {
        // tolerate array of arrays/objects: [{id,title}, …]
        foreach ($pagesForSelect as $p) {
            $id = is_array($p) ? $p['id'] ?? null : $p->id ?? null;
            $title = is_array($p) ? $p['title'] ?? "Page #{$id}" : $p->title ?? "Page #{$id}";
            if ($id !== null) {
                $pagesMap[$id] = $title;
            }
        }
    }
@endphp

<div x-data="{ type: @js($type) }" class="grid sm:grid-cols-2 gap-4">
    <div>
        <label class="text-xs">Homepage displays</label>
        <select name="reading[home_type]" class="w-full rounded border px-2 py-1 text-sm"
            @change="type = $event.target.value">
            <option value="posts" @selected($type === 'posts')>Your latest posts</option>
            <option value="page" @selected($type === 'page')>A static page</option>
        </select>
        <p class="text-[11px] text-red-600" x-text="errors['home_type']"></p>
    </div>

    <div>
        <label class="text-xs">Blog pages show at most</label>
        <input name="reading[posts_per_page]" value="{{ $postsPerPage }}"
            class="w-full rounded border px-2 py-1 text-sm" type="number" min="1" max="100">
        <p class="text-[11px] text-red-600" x-text="errors['posts_per_page']"></p>
    </div>

    <div :class="type !== 'page' ? 'opacity-60' : ''">
        <label class="text-xs">Homepage</label>
        <select name="reading[home_page_id]" class="w-full rounded border px-2 py-1 text-sm"
            :disabled="type !== 'page'">
            <option value="">— Select —</option>
            @foreach ($pagesMap as $id => $title)
                <option value="{{ $id }}" @selected((string) $homePageId === (string) $id)>{{ $title }}</option>
            @endforeach
        </select>
        <p class="text-[11px] text-red-600" x-text="errors['home_page_id']"></p>
    </div>

    <div :class="type !== 'page' ? 'opacity-60' : ''">
        <label class="text-xs">Posts page</label>
        <select name="reading[blog_page_id]" class="w-full rounded border px-2 py-1 text-sm"
            :disabled="type !== 'page'">
            <option value="">— Select —</option>
            @foreach ($pagesMap as $id => $title)
                <option value="{{ $id }}" @selected((string) $blogPageId === (string) $id)>{{ $title }}</option>
            @endforeach
        </select>
        <p class="text-[11px] text-red-600" x-text="errors['blog_page_id']"></p>
    </div>

    <div>
        <label class="text-xs">Syndication feeds show the most recent</label>
        <input name="reading[feed_items]" value="{{ $feedItems }}" class="w-full rounded border px-2 py-1 text-sm"
            type="number" min="1" max="100">
        <p class="text-[11px] text-red-600" x-text="errors['feed_items']"></p>
    </div>

    <div>
        <label class="text-xs">For each article in a feed, show</label>
        <select name="reading[feed_show]" class="w-full rounded border px-2 py-1 text-sm">
            <option value="full" @selected($feedShow === 'full')>Full text</option>
            <option value="summary" @selected($feedShow === 'summary')>Summary</option>
        </select>
        <p class="text-[11px] text-red-600" x-text="errors['feed_show']"></p>
    </div>

    <label class="inline-flex items-center gap-2 sm:col-span-2">
        <input type="checkbox" name="reading[discourage_indexing]" value="1" @checked($discourage)>
        <span class="text-xs">Discourage search engines from indexing this site</span>
    </label>
</div>
