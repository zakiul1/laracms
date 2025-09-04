@extends('admin.layout', ['title' => $type === 'page' ? 'Edit Page' : 'Edit Post'])

@section('content')
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">
            Edit {{ $type === 'page' ? 'Page' : 'Post' }}
        </h1>

        {{-- Delete with global confirm modal --}}
        @php $delId = 'del-'.$type.'-'.$post->id; @endphp
        <form id="{{ $delId }}" method="POST"
            action="{{ route($type === 'page' ? 'admin.pages.destroy' : 'admin.posts.destroy', $post) }}" class="hidden">
            @csrf @method('DELETE')
        </form>
        <button type="button" class="text-red-600"
            @click="window.dispatchEvent(new CustomEvent('confirm', {
                    detail: { message: 'Delete this {{ $type === 'page' ? 'page' : 'post' }}?', submit: '{{ $delId }}' }
                }))">
            Delete
        </button>
    </div>

    @include('admin.posts.form', [
        'post' => $post,
        'type' => $type,
        'categoriesTree' => $categoriesTree ?? [],
        'templates' => $templates ?? [],
    ])
@endsection
