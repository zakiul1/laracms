{{-- resources/views/components/ui/icon.blade.php --}}
@props([
    'name' => 'circle',
    'class' => 'size-5 shrink-0',
])
@php
    $n = \Illuminate\Support\Str::of($name)->replace('lucide-', '')->kebab();
@endphp
<i data-lucide="{{ $n }}" {{ $attributes->merge(['class' => $class]) }} aria-hidden="true"></i>
