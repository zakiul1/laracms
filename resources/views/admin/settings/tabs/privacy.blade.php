@php($v = $values)
<div class="space-y-3">
    <div>
        <label class="text-xs">Privacy Policy Page</label>
        <select name="privacy[policy_page_id]" class="w-full rounded border px-2 py-1 text-sm">
            <option value="">— Select —</option>
            @foreach ($pagesForSelect as $p)
                <option value="{{ $p['id'] }}" @selected($v['policy_page_id'] == $p['id'])>{{ $p['title'] }}</option>
            @endforeach
        </select>
    </div>

    @if (empty($v['policy_page_id']))
        <div class="text-xs p-2 rounded bg-amber-50 border border-amber-200">
            No privacy policy page selected. Please create one and choose it here to show notices to users.
        </div>
    @endif
</div>
