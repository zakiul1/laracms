@extends('admin.layout', ['title' => $type === 'page' ? 'Edit Page' : 'Edit Post'])

@section('content')
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">
            Edit {{ $type === 'page' ? 'Page' : 'Post' }}
        </h1>

        <div class="flex items-center gap-4">
            <a href="{{ route($type === 'page' ? 'admin.pages.index' : 'admin.posts.index') }}"
                class="text-sm text-gray-600 hover:text-gray-900">
                ← Back to {{ $type === 'page' ? 'Pages' : 'Posts' }}
            </a>

            {{-- Delete with global confirm modal --}}
            @php $delId = 'del-'.$type.'-'.$post->id; @endphp
            <form id="{{ $delId }}" method="POST"
                action="{{ route($type === 'page' ? 'admin.pages.destroy' : 'admin.posts.destroy', $post) }}" class="hidden">
                @csrf @method('DELETE')
            </form>
            <button type="button" class="text-red-600 hover:text-red-700"
                @click="window.dispatchEvent(new CustomEvent('confirm', {
                        detail: { message: 'Delete this {{ $type === 'page' ? 'page' : 'post' }}?', submit: '{{ $delId }}' }
                    }))">
                Delete
            </button>
        </div>
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
