@php($v = $values)
<div class="space-y-4">
    <div>
        <div class="font-medium text-sm mb-2">Default article settings</div>
        <label class="inline-flex items-center gap-2 mr-4">
            <input type="checkbox" name="discussion[default_article][notify_blogs]" value="1"
                {{ $v['default_article']['notify_blogs'] ? 'checked' : '' }}>
            <span class="text-xs">Attempt to notify blogs</span>
        </label>
        <label class="inline-flex items-center gap-2 mr-4">
            <input type="checkbox" name="discussion[default_article][allow_pingbacks]" value="1"
                {{ $v['default_article']['allow_pingbacks'] ? 'checked' : '' }}>
            <span class="text-xs">Allow pingbacks & trackbacks</span>
        </label>
        <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="discussion[default_article][allow_comments]" value="1"
                {{ $v['default_article']['allow_comments'] ? 'checked' : '' }}>
            <span class="text-xs">Allow comments</span>
        </label>
    </div>

    <div>
        <div class="font-medium text-sm mb-2">Other comment settings</div>
        <div class="grid sm:grid-cols-2 gap-3">
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="discussion[comments][require_name_email]" value="1"
                    {{ $v['comments']['require_name_email'] ? 'checked' : '' }}>
                <span class="text-xs">Comment author must fill name and email</span>
            </label>
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="discussion[comments][must_be_registered]" value="1"
                    {{ $v['comments']['must_be_registered'] ? 'checked' : '' }}>
                <span class="text-xs">Users must be registered and logged in to comment</span>
            </label>

            <div>
                <label class="text-xs">Close comments after (days)</label>
                <input type="number" min="0" name="discussion[comments][close_after_days]"
                    value="{{ $v['comments']['close_after_days'] }}" class="rounded border px-2 py-1 text-sm w-32">
            </div>

            <div>
                <label class="text-xs">Threaded (nested) comments levels deep</label>
                <input type="number" min="1" max="10" name="discussion[comments][threaded_levels]"
                    value="{{ $v['comments']['threaded_levels'] }}" class="rounded border px-2 py-1 text-sm w-32">
            </div>

            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="discussion[comments][paginate]" value="1"
                    {{ $v['comments']['paginate'] ? 'checked' : '' }}>
                <span class="text-xs">Break comments into pages of</span>
            </label>
            <div>
                <label class="text-xs">Comments per page</label>
                <input type="number" min="5" max="200" name="discussion[comments][per_page]"
                    value="{{ $v['comments']['per_page'] }}" class="rounded border px-2 py-1 text-sm w-32">
            </div>

            <div>
                <label class="text-xs">Comments order</label>
                <select name="discussion[comments][order]" class="rounded border px-2 py-1 text-sm">
                    <option value="asc" @selected($v['comments']['order'] === 'asc')>Oldest first</option>
                    <option value="desc" @selected($v['comments']['order'] === 'desc')>Newest first</option>
                </select>
            </div>

            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="discussion[comments][manual_approve]" value="1"
                    {{ $v['comments']['manual_approve'] ? 'checked' : '' }}>
                <span class="text-xs">Comment must be manually approved</span>
            </label>

            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="discussion[comments][auto_approve_prev]" value="1"
                    {{ $v['comments']['auto_approve_prev'] ? 'checked' : '' }}>
                <span class="text-xs">Comment author must have a previously approved comment</span>
            </label>

            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="discussion[comments][email_notify][new_comment]" value="1"
                    {{ $v['comments']['email_notify']['new_comment'] ? 'checked' : '' }}>
                <span class="text-xs">Email me whenever: someone posts a comment</span>
            </label>

            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="discussion[comments][email_notify][moderation]" value="1"
                    {{ $v['comments']['email_notify']['moderation'] ? 'checked' : '' }}>
                <span class="text-xs">Email me whenever: a comment is held for moderation</span>
            </label>
        </div>
    </div>

    <div>
        <div class="font-medium text-sm mb-2">Avatars</div>
        <label class="inline-flex items-center gap-2 mr-3">
            <input type="checkbox" name="discussion[avatars][show]" value="1"
                {{ $v['avatars']['show'] ? 'checked' : '' }}>
            <span class="text-xs">Show Avatars</span>
        </label>
        <div class="mt-2">
            <label class="text-xs">Default Avatar</label>
            <select name="discussion[avatars][default]" class="rounded border px-2 py-1 text-sm">
                @foreach (['mystery', 'identicon', 'monsterid', 'wavatar', 'retro', 'robohash', 'blank'] as $opt)
                    <option value="{{ $opt }}" @selected($v['avatars']['default'] === $opt)>{{ ucfirst($opt) }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>
