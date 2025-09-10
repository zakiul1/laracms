@extends('admin.layout', ['title' => $type === 'page' ? 'Add Page' : 'Add Post'])

@section('content')
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">
            Add {{ $type === 'page' ? 'Page' : 'Post' }}
        </h1>

        <a href="{{ route($type === 'page' ? 'admin.pages.index' : 'admin.posts.index') }}"
            class="text-sm text-gray-600 hover:text-gray-900">
            ← Back to {{ $type === 'page' ? 'Pages' : 'Posts' }}
        </a>
    </div>

    {{-- Flash success --}}
    @if (session('success'))
        <div class="mb-4 p-3 rounded border border-green-300 bg-green-50 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- Validation errors --}}
    @if ($errors->any())
        <div class="mb-4 p-3 rounded border border-red-300 bg-red-50 text-red-800">
            <div class="font-medium mb-2">Please fix the following:</div>
            <ul class="list-disc list-inside text-sm space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @include('admin.posts.form', [
        'post' => $post,
        'type' => $type,
        'categoriesTree' => $categoriesTree ?? [],
        'templates' => $templates ?? [],
    ])
@endsection
