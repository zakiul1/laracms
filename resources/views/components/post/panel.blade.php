@props(['title' => '', 'open' => false])

<div x-data="{ open: {{ $open ? 'true' : 'false' }} }" class="rounded-radius border border-outline dark:border-outline-dark">
    <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-3 py-2">
        <span class="font-medium text-sm">{{ $title }}</span>
        <svg class="w-4 h-4" x-bind:class="open ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor">
            <path
                d="M5.23 7.21a.75.75 0 011.06.02L10 11.188l3.71-3.957a.75.75 0 111.08 1.04l-4.24 4.53a.75.75 0 01-1.08 0l-4.24-4.53a.75.75 0 01.02-1.06z" />
        </svg>
    </button>
    <div x-show="open" x-collapse class="px-3 pb-3">
        {{ $slot }}
    </div>
</div>
