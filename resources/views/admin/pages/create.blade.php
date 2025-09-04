@extends('admin.layout', ['title' => 'Add Page'])

@section('content')
    @include('admin.posts.form', ['post' => $post, 'type' => 'page'])
@endsection
