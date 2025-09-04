@extends('admin.layout', ['title' => $type === 'page' ? 'Add Page' : 'Add Post'])

@section('content')
    <h1 class="text-xl font-semibold mb-4">
        Add {{ $type === 'page' ? 'Page' : 'Post' }}
    </h1>

    @include('admin.posts.form', [
        'post' => $post,
        'type' => $type,
        'categoriesTree' => $categoriesTree ?? [],
        'templates' => $templates ?? [],
    ])
@endsection
