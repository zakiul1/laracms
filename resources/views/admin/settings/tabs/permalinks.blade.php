@php
    $v = $values ?? [];

    // Current selections with sensible defaults
    $selected = $v['structure'] ?? 'postname';
    $customVal = $v['custom'] ?? '/%postname%/';
    $catBase = $v['category_base'] ?? '/category';
    $tagBase = $v['tag_base'] ?? '/tag';

    // Optional data from controller; provide fallbacks if missing
    $labels = $labels ?? [
        'plain' => 'Plain',
        'dayname' => 'Day and Name',
        'monthname' => 'Month and Name',
        'numeric' => 'Numeric',
        'postname' => 'Post name',
        'custom' => 'Custom Structure',
    ];

    $choices = $choices ?? [
        'plain' => url('/?p=123'),
        'dayname' => '/%year%/%monthnum%/%day%/%postname%/',
        'monthname' => '/%year%/%monthnum%/%postname%/',
        'numeric' => '/archives/%post_id%/',
        'postname' => '/%postname%/',
        'custom' => $customVal,
    ];
@endphp

<div x-data="{ structure: @js($selected) }" class="space-y-3">
    @foreach ($labels as $key => $label)
        <label class="flex items-center gap-3 border rounded p-2">
            <input type="radio" name="permalinks[structure]" value="{{ $key }}"
                :checked="structure === '{{ $key }}'" @change="structure = '{{ $key }}'">
            <div class="text-sm">
                <div class="font-medium">{{ $label }}</div>
                <div class="text-xs opacity-70">
                    {{ $choices[$key] ?? '' }}
                </div>
            </div>
        </label>
    @endforeach
    <p class="text-[11px] text-red-600" x-text="errors['structure']"></p>

    <div class="mt-1">
        <label class="text-xs">Custom Structure</label>
        <input name="permalinks[custom]" value="{{ $customVal }}" class="w-full rounded border px-2 py-1 text-sm"
            placeholder="/%postname%/" :disabled="structure !== 'custom'">
        <p class="text-[11px] text-red-600" x-text="errors['custom']"></p>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="text-xs">Category base</label>
            <input name="permalinks[category_base]" value="{{ $catBase }}"
                class="w-full rounded border px-2 py-1 text-sm" placeholder="/category">
            <p class="text-[11px] text-red-600" x-text="errors['category_base']"></p>
        </div>
        <div>
            <label class="text-xs">Tag base</label>
            <input name="permalinks[tag_base]" value="{{ $tagBase }}"
                class="w-full rounded border px-2 py-1 text-sm" placeholder="/tag">
            <p class="text-[11px] text-red-600" x-text="errors['tag_base']"></p>
        </div>
    </div>

    <p class="text-[11px] opacity-70">
        Note: applying permalink changes to routes is app-specific. These values are saved here for your URL generators.
    </p>
</div>
