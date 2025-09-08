@php
    $c = isset($widget) ? $widget : $w;
    $id = $c->id;
    $settings = $c->settings ?? [];
    $content = $settings['content'] ?? '';
@endphp

<textarea class="rounded border px-2 py-1 text-sm w-full" placeholder="HTML content"
    @change='save({{ $id }}, { settings: { ...(@json($settings)), content: $event.target.value } })'>{{ $content }}</textarea>
