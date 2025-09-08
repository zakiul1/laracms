@php
    $c = isset($widget) ? $widget : $w;
    $id = $c->id;
    $settings = $c->settings ?? [];
    $current = $settings['menu_id'] ?? null;
    $menus = $menus ?? \App\Models\Menu::orderBy('name')->get(['id', 'name']);
@endphp

<label class="text-xs">Menu</label>
<select class="rounded border px-2 py-1 text-sm"
    @change='save({{ $id }}, { settings: { ...(@json($settings)), menu_id: ($event.target.value ? +$event.target.value : null) } })'>
    <option value="">Select menu…</option>
    @foreach ($menus as $m)
        <option value="{{ $m->id }}" @selected($current == $m->id)>{{ $m->name }}</option>
    @endforeach
</select>
