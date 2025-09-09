@extends('admin.layout', ['title' => 'Themes'])

@section('content')
    {{-- Flash messages --}}
    @if (session('success') || session('error') || session('warning'))
        @php
            $type = session('error') ? 'error' : (session('warning') ? 'warning' : 'success');
            $bg = [
                'success' =>
                    'bg-green-50 border-green-200 text-green-800 dark:bg-green-950/40 dark:border-green-900 dark:text-green-200',
                'error' =>
                    'bg-red-50 border-red-200 text-red-800 dark:bg-red-950/40 dark:border-red-900 dark:text-red-200',
                'warning' =>
                    'bg-amber-50 border-amber-200 text-amber-800 dark:bg-amber-950/40 dark:border-amber-900 dark:text-amber-200',
            ][$type];
            $msg = session('error') ?? (session('warning') ?? session('success'));
        @endphp
        <div class="mb-3 border rounded-radius px-3 py-2 text-sm {{ $bg }}" role="status" aria-live="polite">
            {{ $msg }}
        </div>
    @endif

    <div class="flex items-start justify-between mb-4 gap-4">
        <div>
            <h1 class="text-xl font-semibold">Themes</h1>
            <p class="text-xs opacity-70">Manage installed themes, activate one, or upload a new theme (.zip).</p>
        </div>

        {{-- Upload form --}}
        <form method="POST" action="{{ route('admin.appearance.themes.upload') }}" enctype="multipart/form-data"
            class="flex items-center gap-2 border border-outline dark:border-outline-dark rounded-radius p-2"
            x-data="{ submitting: false }" x-on:submit="submitting=true">
            @csrf
            <input type="file" name="zip" accept=".zip"
                class="text-sm file:mr-2 file:px-2 file:py-1.5 file:border file:rounded-radius file:bg-surface-alt
                          dark:file:bg-surface-dark-alt file:border-outline dark:file:border-outline-dark"
                required {{ old('zip') ? '' : '' }}>
            <button type="submit" class="px-3 py-1.5 rounded-radius bg-primary text-white text-sm disabled:opacity-60"
                :disabled="submitting">
                <span x-show="!submitting">Upload</span>
                <span x-show="submitting">Uploading…</span>
            </button>
        </form>
    </div>

    @if (empty($themes))
        <div class="rounded-radius border border-outline dark:border-outline-dark p-6 text-sm">
            No themes found. Upload a theme (.zip) to get started.
        </div>
    @else
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($themes as $t)
                @php
                    $slug = $t['slug'] ?? 'theme';
                    $name = $t['name'] ?? ($t['meta']['name'] ?? ucfirst($slug));
                    $desc = $t['description'] ?? ($t['meta']['description'] ?? '');
                    $author = $t['author'] ?? ($t['meta']['author'] ?? '');
                    $version = $t['version'] ?? ($t['meta']['version'] ?? '');
                    $shot = $t['screenshot_url'] ?? ($t['screenshot'] ?? null);
                    $status = $t['status'] ?? null; // 'active' | 'installed'
                    $isActive = $status === 'active';
                @endphp

                <div
                    class="border border-outline dark:border-outline-dark rounded-radius overflow-hidden bg-white dark:bg-neutral-900 shadow-sm">
                    <div class="relative">
                        <div class="aspect-[16/10] bg-surface-alt dark:bg-surface-dark-alt">
                            @if ($shot)
                                <img src="{{ $shot }}" alt="{{ $name }} screenshot"
                                    class="w-full h-full object-cover" loading="lazy" decoding="async">
                            @else
                                <div class="w-full h-full grid place-items-center text-xs opacity-60">No screenshot</div>
                            @endif
                        </div>

                        @if ($isActive)
                            <span
                                class="absolute left-2 top-2 inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-green-600 text-white">
                                Active
                            </span>
                        @endif
                    </div>

                    <div class="p-3">
                        <div class="font-medium">{{ $name }}</div>
                        <div class="text-xs opacity-70">
                            {{ $slug }}
                            @if ($version)
                                · v{{ $version }}
                            @endif
                            @if ($author)
                                · by {{ $author }}
                            @endif
                        </div>

                        @if ($desc)
                            <p class="text-sm mt-2 line-clamp-3">{{ $desc }}</p>
                        @endif

                        <div class="flex flex-wrap items-center gap-2 mt-3">
                            {{-- Activate / Active --}}
                            <form method="POST" action="{{ route('admin.appearance.themes.activate', $slug) }}"
                                x-data="{ submitting: false }" x-on:submit="submitting=true">
                                @csrf
                                <button type="submit"
                                    class="px-3 py-1.5 rounded-radius text-sm border disabled:opacity-60 disabled:cursor-not-allowed
                                           {{ $isActive
                                               ? 'cursor-not-allowed border-outline dark:border-outline-dark'
                                               : 'border-primary text-primary hover:bg-primary/10' }}"
                                    title="{{ $isActive ? 'Currently active' : 'Set as active theme' }}"
                                    {{ $isActive ? 'disabled' : '' }}
                                    :disabled="submitting || {{ $isActive ? 'true' : 'false' }}">
                                    <span x-show="!submitting">{{ $isActive ? 'Active' : 'Activate' }}</span>
                                    <span x-show="submitting">Activating…</span>
                                </button>
                            </form>

                            {{-- Customize (only for active theme) --}}
                            @if ($isActive && \Route::has('admin.appearance.customize'))
                                <a href="{{ route('admin.appearance.customize') }}"
                                    class="px-3 py-1.5 rounded-radius border border-primary text-primary text-sm hover:bg-primary/10"
                                    title="Customize the active theme">
                                    Customize
                                </a>
                            @endif

                            {{-- Deactivate (optional; only shown if route exists) --}}
                            @if ($isActive && \Route::has('admin.appearance.themes.deactivate'))
                                <form method="POST" action="{{ route('admin.appearance.themes.deactivate', $slug) }}"
                                    x-data="{ submitting: false }" x-on:submit="submitting=true">
                                    @csrf
                                    <button type="submit"
                                        class="px-3 py-1.5 rounded-radius text-sm border border-outline dark:border-outline-dark hover:bg-surface-alt dark:hover:bg-surface-dark-alt disabled:opacity-60"
                                        :disabled="submitting">
                                        <span x-show="!submitting">Deactivate</span>
                                        <span x-show="submitting">Deactivating…</span>
                                    </button>
                                </form>
                            @endif

                            {{-- Preview (/?__theme=slug) --}}
                            <a href="{{ route('admin.appearance.themes.preview', $slug) }}" target="_blank" rel="noopener"
                                class="px-3 py-1.5 rounded-radius border border-outline dark:border-outline-dark text-sm hover:bg-surface-alt dark:hover:bg-surface-dark-alt">
                                Preview
                            </a>

                            {{-- Delete (disabled if active) --}}
                            <form method="POST" action="{{ route('admin.appearance.themes.delete', $slug) }}"
                                @if (!$isActive) onsubmit="return confirm('Delete the &quot;{{ addslashes($name) }}&quot; theme? This removes the theme files.');" @endif
                                x-data="{ submitting: false }" x-on:submit="submitting={{ $isActive ? 'false' : 'true' }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="px-3 py-1.5 rounded-radius border text-sm disabled:opacity-40 disabled:cursor-not-allowed
                                           {{ $isActive ? 'border-outline dark:border-outline-dark' : 'border-red-600 text-red-600 hover:bg-red-50' }}"
                                    title="{{ $isActive ? 'Cannot delete the active theme' : 'Delete theme' }}"
                                    {{ $isActive ? 'disabled' : '' }}
                                    :disabled="submitting || {{ $isActive ? 'true' : 'false' }}">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
