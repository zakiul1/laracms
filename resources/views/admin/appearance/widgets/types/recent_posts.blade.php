@php
    $c = isset($widget) ? $widget : $w;
    $id = $c->id;
    $settings = $c->settings ?? [];
    $limit = (int) ($settings['limit'] ?? 5);
@endphp

<label class="text-xs">Limit</label>
<input type="number" min="1" class="rounded border px-2 py-1 text-sm w-24" value="{{ $limit }}"
    @change='save({{ $id }}, { settings: { ...(@json($settings)), limit: +$event.target.value } })'>
