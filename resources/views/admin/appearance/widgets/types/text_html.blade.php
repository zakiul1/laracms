@php
    // Works whether you pass $widget or $w
    $current = isset($widget) ? $widget : (isset($w) ? $w : null);
    $id = $current?->id;
    $settings = $current?->settings ?? [];
    $content = $settings['content'] ?? '';
@endphp

<textarea class="rounded border px-2 py-1 text-sm w-full" placeholder="HTML content"
    @change="save({{ $id }}, { settings: { ...(@json($settings)), content: $event.target.value } })">{{ $content }}</textarea>
