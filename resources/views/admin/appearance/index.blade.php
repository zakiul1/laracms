@extends('admin.layout', ['title' => 'Appearance'])

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-semibold">Themes</h1>
        <form action="{{ route('admin.appearance.install') }}" method="post" enctype="multipart/form-data"
            class="flex items-center gap-2">
            @csrf
            <input type="file" name="zip" accept=".zip" class="text-sm">
            <button class="px-3 py-2 border rounded">Install ZIP</button>
        </form>
    </div>

    @if (session('ok'))
        <div class="mb-3 text-sm p-2 rounded border border-green-300 bg-green-50">{{ session('ok') }}</div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($list as $t)
            <div class="border rounded-radius overflow-hidden">
                <div class="aspect-[16/9] bg-gray-100 flex items-center justify-center">
                    @if ($t['screenshot'])
                        <img src="{{ Str::startsWith($t['screenshot'], public_path()) ? Str::after($t['screenshot'], public_path()) : asset(Str::startsWith($t['screenshot'], 'http') ? $t['screenshot'] : str_replace(base_path() . DIRECTORY_SEPARATOR, '', $t['screenshot'])) }}"
                            alt="{{ $t['name'] }}" class="w-full h-full object-cover">
                    @else
                        <div class="text-xs opacity-60 p-4">No screenshot</div>
                    @endif
                </div>
                <div class="p-3 space-y-1">
                    <div class="font-medium">{{ $t['name'] }}</div>
                    <div class="text-xs opacity-70">Slug: {{ $t['slug'] }}</div>
                    @if ($t['version'])
                        <div class="text-xs opacity-70">Version: {{ $t['version'] }}</div>
                    @endif
                    @if ($t['author'])
                        <div class="text-xs opacity-70">Author: {{ $t['author'] }}</div>
                    @endif
                    <div class="pt-2 flex flex-wrap gap-2">
                        @if ($active === $t['slug'])
                            <a href="{{ route('admin.appearance.customize') }}"
                                class="px-3 py-1.5 border rounded">Customize</a>
                            <form action="{{ route('admin.appearance.deactivate') }}" method="post">@csrf
                                <button class="px-3 py-1.5 border rounded">Deactivate</button>
                            </form>
                            <span class="text-xs px-2 py-1 rounded bg-green-100 border border-green-300">Active</span>
                        @else
                            <form action="{{ route('admin.appearance.activate', $t['slug']) }}" method="post">@csrf
                                <button class="px-3 py-1.5 border rounded">Activate</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
