@extends('admin.layout', ['title' => 'Header Image'])

@php
    $values = $values ?? [];
    $activeTheme = $activeTheme ?? config('laracms.active_theme', 'laracms');

    $imageId = (int) ($values['image_id'] ?? 0);
    $height = (int) ($values['height'] ?? 320);
    $position = (string) ($values['position'] ?? 'center center');
    $size = (string) ($values['size'] ?? 'cover');
    $repeat = (string) ($values['repeat'] ?? 'no-repeat');
    $overlay = (string) ($values['overlay_color'] ?? '#000000');
    $opacity = (float) ($values['overlay_opacity'] ?? 0.25);
    $showTitle = (bool) ($values['show_title'] ?? true);
@endphp

@section('content')
    <div class="mb-4">
        <h1 class="text-xl font-semibold">Header Image</h1>
        <p class="text-xs opacity-70">Active theme: <span class="font-medium">{{ $activeTheme }}</span></p>
    </div>

    <form method="POST" action="{{ route('admin.appearance.header.save') }}" class="grid grid-cols-1 lg:grid-cols-3 gap-4"
        x-data="{
            imageId: {{ $imageId }},
            height: {{ $height }},
            position: @js($position),
            size: @js($size),
            repeat: @js($repeat),
            overlay: @js($overlay),
            opacity: {{ $opacity }},
            showTitle: {{ $showTitle ? 'true' : 'false' }},
            headerStyle() {
                return 'background-repeat:' + this.repeat + ';background-position:' + this.position + ';background-size:' + this.size + ';';
            },
            overlayStyle() { return 'background:' + this.overlay + ';opacity:' + this.opacity + ';'; }
        }">
        @csrf

        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-radius border border-outline dark:border-outline-dark p-3">
                <label class="block text-sm mb-2">Header Image</label>
                <x-media-picker name="header[image_id]" :multiple="false" :value="$imageId ?: null" label="Choose / Select Image" />
            </div>

            <div
                class="rounded-radius border border-outline dark:border-outline-dark p-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs mb-1">Height (px)</label>
                    <input type="number" min="120" max="1200" step="10" x-model.number="height"
                        name="header[height]"
                        class="w-full border border-outline rounded-radius px-2 py-1.5 text-sm dark:border-outline-dark dark:bg-surface-dark/50">
                </div>
                <div>
                    <label class="block text-xs mb-1">Repeat</label>
                    <select x-model="repeat" name="header[repeat]"
                        class="w-full border border-outline rounded-radius px-2 py-2 dark:border-outline-dark dark:bg-surface-dark/50">
                        <option value="no-repeat">No repeat</option>
                        <option value="repeat">Repeat</option>
                        <option value="repeat-x">Repeat X</option>
                        <option value="repeat-y">Repeat Y</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs mb-1">Position</label>
                    <select x-model="position" name="header[position]"
                        class="w-full border border-outline rounded-radius px-2 py-2 dark:border-outline-dark dark:bg-surface-dark/50">
                        <option>left top</option>
                        <option>left center</option>
                        <option>left bottom</option>
                        <option>center top</option>
                        <option selected>center center</option>
                        <option>center bottom</option>
                        <option>right top</option>
                        <option>right center</option>
                        <option>right bottom</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs mb-1">Size</label>
                    <select x-model="size" name="header[size]"
                        class="w-full border border-outline rounded-radius px-2 py-2 dark:border-outline-dark dark:bg-surface-dark/50">
                        <option>auto</option>
                        <option selected>cover</option>
                        <option>contain</option>
                    </select>
                </div>
            </div>

            <div
                class="rounded-radius border border-outline dark:border-outline-dark p-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs mb-1">Overlay Color</label>
                    <div class="flex items-center gap-3">
                        <input type="color" x-model="overlay" class="h-9 w-14 border border-outline rounded-radius p-0">
                        <input type="text" x-model="overlay" name="header[overlay_color]"
                            class="flex-1 border border-outline rounded-radius px-2 py-1.5 text-sm dark:border-outline-dark dark:bg-surface-dark/50">
                    </div>
                </div>
                <div>
                    <label class="block text-xs mb-1">Overlay Opacity</label>
                    <input type="range" min="0" max="1" step="0.01" x-model.number="opacity"
                        class="w-full">
                    <input type="number" min="0" max="1" step="0.01" x-model.number="opacity"
                        name="header[overlay_opacity]"
                        class="mt-1 w-full border border-outline rounded-radius px-2 py-1.5 text-sm dark:border-outline-dark dark:bg-surface-dark/50">
                </div>
                <div class="md:col-span-2">
                    <label class="inline-flex items-center gap-2 text-xs">
                        <input type="checkbox" x-model="showTitle" name="header[show_title]" value="1">
                        Show Site Title/Tagline over header
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="px-3 py-1.5 rounded-radius bg-primary text-white text-sm">Save Header</button>
                <a href="{{ route('admin.appearance.header') }}"
                    class="px-3 py-1.5 rounded-radius border border-outline text-sm">Reset</a>
            </div>
        </div>

        <aside class="space-y-4">
            <div class="rounded-radius border border-outline dark:border-outline-dark overflow-hidden">
                <div
                    class="px-3 py-2 border-b border-outline dark:border-outline-dark text-xs font-medium bg-surface-alt dark:bg-surface-dark-alt">
                    Live Preview
                </div>
                <div class="relative" :style="headerStyle()">
                    <div class="absolute inset-0" :style="overlayStyle()"></div>
                    <div class="relative px-6 py-8" :style="`height:${height}px`">
                        <div class="max-w-[420px] bg-white/85 rounded-radius shadow p-4" x-show="showTitle">
                            <div class="text-xs opacity-70 mb-1">Site Title</div>
                            <div class="text-lg font-semibold">Your Awesome Site</div>
                            <div class="text-xs opacity-70">Just another tagline</div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </form>
@endsection
