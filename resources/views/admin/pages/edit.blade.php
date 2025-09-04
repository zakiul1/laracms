@extends('admin.layout', ['title' => 'Edit Page'])

@section('content')
    @include('admin.posts.form', ['post' => $post, 'type' => 'page'])
@endsection
