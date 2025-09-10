@extends('admin.layout', ['title' => 'Background'])

@php
    // Controller can pass $values (array) with current settings.
    // Fallback to old() so the form repopulates after validation errors.
    $v = old('background', $values ?? []);
    $active = $activeTheme ?? config('laracms.active_theme', 'laracms');
@endphp

@section('content')
    <div class="mb-4">
        <h1 class="text-xl font-semibold">Background</h1>
        <p class="text-xs opacity-70">
            Configure site background for the active theme:
            <span class="font-medium">{{ $active }}</span>
        </p>
    </div>

    <form method="POST" action="{{ route('admin.appearance.background.save') }}" class="grid grid-cols-1 lg:grid-cols-3 gap-4"
        x-data="{
            color: '{{ $v['color'] ?? '#ffffff' }}',
            repeat: '{{ $v['repeat'] ?? 'no-repeat' }}',
            position: '{{ $v['position'] ?? 'center center' }}',
            size: '{{ $v['size'] ?? 'cover' }}',
            attachment: '{{ $v['attachment'] ?? 'scroll' }}',
            imageId: '{{ (int) ($v['image_id'] ?? 0) }}',
        }">
        @csrf

        {{-- LEFT: Controls --}}
        <div class="lg:col-span-2 space-y-4">
            {{-- Color --}}
            <div class="rounded-radius border border-outline dark:border-outline-dark p-3">
                <label class="block text-sm mb-2">Background Color</label>
                <div class="flex items-center gap-3">
                    <input type="color" x-model="color" class="h-9 w-14 border border-outline rounded-radius p-0">
                    <input type="text" x-model="color" name="background[color]"
                        class="flex-1 border border-outline rounded-radius px-2 py-1.5 text-sm dark:border-outline-dark dark:bg-surface-dark/50">
                </div>
            </div>

            {{-- Image --}}
            <div class="rounded-radius border border-outline dark:border-outline-dark p-3">
                <label class="block text-sm mb-2">Background Image</label>

                {{-- Media picker (single) --}}
                <x-media-picker name="background[image_id]" :multiple="false" :value="$v['image_id'] ?? null"
                    label="Choose / Select Image" />

                <p class="text-xs opacity-70 mt-2">
                    Optional. If set, the image will be used behind the site content.
                </p>
            </div>

            {{-- Image options --}}
            <div
                class="rounded-radius border border-outline dark:border-outline-dark p-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs mb-1">Repeat</label>
                    <select x-model="repeat" name="background[repeat]"
                        class="w-full border border-outline rounded-radius px-2 py-2 dark:border-outline-dark dark:bg-surface-dark/50">
                        <option value="no-repeat">No repeat</option>
                        <option value="repeat">Repeat</option>
                        <option value="repeat-x">Repeat X</option>
                        <option value="repeat-y">Repeat Y</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs mb-1">Position</label>
                    <select x-model="position" name="background[position]"
                        class="w-full border border-outline rounded-radius px-2 py-2 dark:border-outline-dark dark:bg-surface-dark/50">
                        <option value="left top">Left Top</option>
                        <option value="left center">Left Center</option>
                        <option value="left bottom">Left Bottom</option>
                        <option value="center top">Center Top</option>
                        <option value="center center">Center Center</option>
                        <option value="center bottom">Center Bottom</option>
                        <option value="right top">Right Top</option>
                        <option value="right center">Right Center</option>
                        <option value="right bottom">Right Bottom</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs mb-1">Size</label>
                    <select x-model="size" name="background[size]"
                        class="w-full border border-outline rounded-radius px-2 py-2 dark:border-outline-dark dark:bg-surface-dark/50">
                        <option value="auto">Auto</option>
                        <option value="cover">Cover</option>
                        <option value="contain">Contain</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs mb-1">Attachment</label>
                    <select x-model="attachment" name="background[attachment]"
                        class="w-full border border-outline rounded-radius px-2 py-2 dark:border-outline-dark dark:bg-surface-dark/50">
                        <option value="scroll">Scroll</option>
                        <option value="fixed">Fixed</option>
                        <option value="local">Local</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="px-3 py-1.5 rounded-radius bg-primary text-white text-sm">
                    Save Background
                </button>
                <a href="{{ route('admin.appearance.background') }}"
                    class="px-3 py-1.5 rounded-radius border border-outline text-sm">
                    Reset
                </a>
            </div>
        </div>

        {{-- RIGHT: Live Preview --}}
        <aside class="space-y-4">
            <div class="rounded-radius border border-outline dark:border-outline-dark overflow-hidden">
                <div
                    class="px-3 py-2 border-b border-outline dark:border-outline-dark text-xs font-medium bg-surface-alt dark:bg-surface-dark-alt">
                    Live Preview
                </div>
                <div id="bg-preview" class="p-6 min-h-[300px] text-sm" style="transition: background 120ms ease-in-out;"
                    x-init="$watch('color', apply);
                    $watch('repeat', apply);
                    $watch('position', apply);
                    $watch('size', apply);
                    $watch('attachment', apply);
                    apply()" x-data="{
                        apply() {
                            const box = $el;
                            box.style.backgroundColor = color;
                            // Image is previewed via inline style that the media-picker could set on change
                            // If you want instant preview from picker, dispatch a CustomEvent & listen here.
                            box.style.backgroundRepeat = repeat;
                            box.style.backgroundPosition = position;
                            box.style.backgroundSize = size;
                            box.style.backgroundAttachment = attachment;
                        }
                    }">
                    <div class="max-w-[240px] bg-white/85 rounded-radius shadow p-3">
                        <div class="text-xs opacity-70 mb-1">This is a preview box.</div>
                        <p>
                            Your background color / image settings will appear here.
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-radius border border-outline dark:border-outline-dark p-3 text-xs opacity-70">
                Tip: background is saved per theme (“{{ $active }}”).
                Make sure your theme’s layout applies the saved options to the <code>body</code> or page wrapper.
            </div>
        </aside>
    </form>
@endsection
