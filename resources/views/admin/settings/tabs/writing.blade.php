@php($v = $values)
<div class="grid sm:grid-cols-2 gap-4">
    <div>
        <label class="text-xs">Default Post Category (ID)</label>
        <input name="writing[default_post_category]" value="{{ $v['default_post_category'] }}"
            class="w-full rounded border px-2 py-1 text-sm" placeholder="e.g., 1">
    </div>

    <div>
        <label class="text-xs">Default Post Format</label>
        <select name="writing[default_post_format]" class="w-full rounded border px-2 py-1 text-sm">
            @foreach (['standard', 'aside', 'gallery', 'link', 'image', 'quote', 'status', 'video', 'audio', 'chat'] as $fmt)
                <option value="{{ $fmt }}" @selected($v['default_post_format'] === $fmt)>{{ ucfirst($fmt) }}</option>
            @endforeach
        </select>
    </div>

    <div class="sm:col-span-2 border-t pt-3">
        <div class="font-medium text-sm mb-2">Post via Email</div>
        <div class="grid sm:grid-cols-2 gap-3">
            <input name="writing[post_via_email][host]" value="{{ $v['post_via_email']['host'] ?? '' }}"
                class="rounded border px-2 py-1 text-sm" placeholder="Mail server host">
            <input name="writing[post_via_email][port]" value="{{ $v['post_via_email']['port'] ?? 993 }}"
                class="rounded border px-2 py-1 text-sm" placeholder="Port">
            <input name="writing[post_via_email][user]" value="{{ $v['post_via_email']['user'] ?? '' }}"
                class="rounded border px-2 py-1 text-sm" placeholder="Login name">
            <input name="writing[post_via_email][pass]" value="{{ $v['post_via_email']['pass'] ?? '' }}"
                class="rounded border px-2 py-1 text-sm" placeholder="Password">
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="writing[post_via_email][ssl]" value="1"
                    {{ !empty($v['post_via_email']['ssl']) ? 'checked' : '' }}>
                <span class="text-xs">Use SSL</span>
            </label>
            <input name="writing[post_via_email][default_category]"
                value="{{ $v['post_via_email']['default_category'] ?? '' }}" class="rounded border px-2 py-1 text-sm"
                placeholder="Default category ID">
        </div>
    </div>

    <div class="sm:col-span-2">
        <label class="text-xs">Update Services (one per line)</label>
        <textarea name="writing[update_services]" rows="4" class="w-full rounded border px-2 py-1 text-sm">{{ $v['update_services'] }}</textarea>
    </div>
</div>
