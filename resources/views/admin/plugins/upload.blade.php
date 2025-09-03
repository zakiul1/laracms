@extends('admin.layout', ['title' => 'Upload Plugin'])

@section('content')
    <h1 class="text-xl font-semibold mb-4">Upload Plugin (ZIP)</h1>

    @if ($errors->any())
        <div class="mb-3 text-red-700 bg-red-50 border border-red-200 rounded p-2">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if (session('success'))
        <div class="mb-3 text-green-700 bg-green-50 border border-green-200 rounded p-2">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.plugins.upload') }}" enctype="multipart/form-data" class="space-y-4 max-w-lg">
        @csrf
        <div class="border rounded-radius p-4">
            <label class="block text-sm font-medium mb-2">Plugin ZIP</label>
            <input type="file" name="zip" accept=".zip" required class="w-full border rounded-radius px-3 py-2">
            <p class="text-xs text-muted-foreground mt-1">Max {{ config('plugins.max_upload_mb', 50) }} MB</p>
        </div>
        <div>
            <button class="px-4 py-2 border rounded-radius">Upload & Install</button>
        </div>
    </form>
@endsection
