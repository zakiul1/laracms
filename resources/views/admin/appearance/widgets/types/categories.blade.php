@php
    $c = isset($widget) ? $widget : $w;
    $id = $c->id;
    $settings = $c->settings ?? [];
    $dropdown = !empty($settings['dropdown']);
@endphp

<label class="text-xs inline-flex items-center gap-2">
    <input type="checkbox" {{ $dropdown ? 'checked' : '' }}
        @change='save({{ $id }}, { settings: { ...(@json($settings)), dropdown: $event.target.checked } })'>
    Dropdown
</label>
