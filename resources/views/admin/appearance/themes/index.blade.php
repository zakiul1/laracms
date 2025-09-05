@extends('admin.layout', ['title' => 'Themes'])

@section('content')
    <div class="flex items-start justify-between mb-4 gap-4">
        <div>
            <h1 class="text-xl font-semibold">Themes</h1>
            <p class="text-xs opacity-70">Manage installed themes, activate one, or upload a new theme (.zip).</p>
        </div>

        {{-- Upload form --}}
        <form method="POST" action="{{ route('admin.appearance.themes.upload') }}" enctype="multipart/form-data"
            class="flex items-center gap-2 border border-outline dark:border-outline-dark rounded-radius p-2">
            @csrf
            <input type="file" name="zip" accept=".zip"
                class="text-sm file:mr-2 file:px-2 file:py-1.5 file:border file:rounded-radius file:bg-surface-alt
                          dark:file:bg-surface-dark-alt file:border-outline dark:file:border-outline-dark"
                required>
            <button type="submit" class="px-3 py-1.5 rounded-radius bg-primary text-white text-sm">Upload</button>
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
                    // Normalized fields from controller (with safe fallbacks)
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
                                    class="w-full h-full object-cover">
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
                            <form method="POST" action="{{ route('admin.appearance.themes.activate', $slug) }}">
                                @csrf
                                <button type="submit"
                                    class="px-3 py-1.5 rounded-radius text-sm border
                                               {{ $isActive
                                                   ? 'opacity-60 cursor-not-allowed border-outline dark:border-outline-dark'
                                                   : 'border-primary text-primary hover:bg-primary/10' }}"
                                    {{ $isActive ? 'disabled' : '' }}>
                                    {{ $isActive ? 'Active' : 'Activate' }}
                                </button>
                            </form>

                            {{-- Deactivate (optional; only shown if route exists) --}}
                            @if ($isActive && Route::has('admin.appearance.themes.deactivate'))
                                <form method="POST" action="{{ route('admin.appearance.themes.deactivate', $slug) }}">
                                    @csrf
                                    <button type="submit"
                                        class="px-3 py-1.5 rounded-radius text-sm border border-outline dark:border-outline-dark hover:bg-surface-alt dark:hover:bg-surface-dark-alt">
                                        Deactivate
                                    </button>
                                </form>
                            @endif

                            {{-- Preview (/?__theme=slug) --}}
                            <a href="{{ route('admin.appearance.themes.preview', $slug) }}" target="_blank"
                                class="px-3 py-1.5 rounded-radius border border-outline dark:border-outline-dark text-sm hover:bg-surface-alt dark:hover:bg-surface-dark-alt">
                                Preview
                            </a>

                            {{-- Delete (disabled if active) --}}
                            <form method="POST" action="{{ route('admin.appearance.themes.delete', $slug) }}"
                                onsubmit="return {{ $isActive ? 'false' : 'confirm(\'Delete the &quot;' . addslashes($name) . '&quot; theme? This removes the theme files.\')' }};">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="px-3 py-1.5 rounded-radius border text-sm
                                               {{ $isActive
                                                   ? 'opacity-40 cursor-not-allowed border-outline dark:border-outline-dark'
                                                   : 'border-red-600 text-red-600 hover:bg-red-50' }}"
                                    {{ $isActive ? 'disabled' : '' }}>
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
