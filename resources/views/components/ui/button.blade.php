@props([
    'type' => 'button',
    'variant' => 'secondary', // primary | secondary | outline | ghost | danger
    'size' => 'md', // sm | md | lg
    'href' => null,
    'disabled' => false,
])

@php
    $base = 'inline-flex items-center gap-1 rounded-md border transition
             focus:outline-none focus:ring-2 focus:ring-offset-1
             disabled:opacity-60 disabled:cursor-not-allowed';

    $variants = [
        'primary' => 'bg-indigo-600 text-white border-indigo-600 hover:bg-indigo-500',
        'secondary' =>
            'bg-white text-gray-900 border-gray-300 hover:bg-gray-50 dark:bg-neutral-800 dark:text-white dark:border-neutral-700',
        'outline' =>
            'bg-transparent text-gray-900 border-gray-300 hover:bg-gray-50 dark:text-white dark:border-neutral-700 dark:hover:bg-neutral-800/50',
        'ghost' =>
            'bg-transparent text-gray-700 border-transparent hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-neutral-800/50',
        'danger' => 'bg-red-600 text-white border-red-600 hover:bg-red-500',
    ];

    $sizes = [
        'sm' => 'text-xs px-2.5 py-1.5',
        'md' => 'text-sm px-3 py-2',
        'lg' => 'text-base px-4 py-2.5',
    ];

    $classes = trim(
        $base . ' ' . ($variants[$variant] ?? $variants['secondary']) . ' ' . ($sizes[$size] ?? $sizes['md']),
    );
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" @disabled($disabled) {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
