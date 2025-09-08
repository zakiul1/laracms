@php($v = $values)
<div class="grid sm:grid-cols-3 gap-4">
    @foreach (['thumbnail' => 'Thumbnail', 'medium' => 'Medium', 'large' => 'Large'] as $key => $label)
        <div class="border rounded p-3">
            <div class="font-medium text-sm mb-2">{{ $label }} size</div>
            <label class="text-xs">Width</label>
            <input type="number" min="1" max="12000" name="media[sizes][{{ $key }}][w]"
                value="{{ $v['sizes'][$key]['w'] }}" class="w-full rounded border px-2 py-1 text-sm">
            <label class="text-xs mt-2">Height</label>
            <input type="number" min="1" max="12000" name="media[sizes][{{ $key }}][h]"
                value="{{ $v['sizes'][$key]['h'] }}" class="w-full rounded border px-2 py-1 text-sm">
            <label class="inline-flex items-center gap-2 mt-2">
                <input type="checkbox" name="media[sizes][{{ $key }}][crop]" value="1"
                    {{ $v['sizes'][$key]['crop'] ? 'checked' : '' }}>
                <span class="text-xs">Crop to exact dimensions</span>
            </label>
        </div>
    @endforeach
</div>

<label class="inline-flex items-center gap-2 mt-4">
    <input type="checkbox" name="media[organize_uploads_by_date]" value="1"
        {{ $v['organize_uploads_by_date'] ? 'checked' : '' }}>
    <span class="text-xs">Organize my uploads into month- and year-based folders</span>
</label>
