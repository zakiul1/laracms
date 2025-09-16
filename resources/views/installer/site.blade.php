@extends('installer.layout')

@section('content')
    <h2 class="text-xl font-semibold mb-4">Site Information</h2>

    <form method="POST" action="{{ route('install.site.save') }}" class="grid gap-4">
        @csrf
        <div>
            <label class="block text-sm mb-1">Site Title</label>
            <input name="title" value="{{ old('title', $defaults['title']) }}" class="w-full border rounded p-2">
            @error('title')
                <div class="text-sm text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <label class="block text-sm mb-1">Site URL</label>
            <input name="url" value="{{ old('url', $defaults['url']) }}" class="w-full border rounded p-2">
            @error('url')
                <div class="text-sm text-red-600">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="px-4 py-2 rounded-lg bg-black text-white">Continue</button>
    </form>
@endsection
