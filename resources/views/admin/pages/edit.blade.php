@extends('admin.layout', ['title' => 'Edit Page'])

@section('content')
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">Edit Page</h1>

        @php $delId = 'del-page-'.$post->id; @endphp
        <form id="{{ $delId }}" method="POST" action="{{ route('admin.pages.destroy', $post) }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>

        <button type="button" class="text-red-600"
            onclick="if (confirm('Delete this page?')) document.getElementById('{{ $delId }}').submit();">
            Delete
        </button>
    </div>

    @include('admin.posts.form', [
        'post' => $post,
        'type' => 'page',
        'categoriesTree' => [],
        'templates' => $templates ?? [],
    ])
@endsection
