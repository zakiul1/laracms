@extends('admin.layout', ['title' => 'Pages'])
@section('content')
    @include('admin.posts.index', ['screen' => 'Pages', 'items' => $items, 'type' => 'page'])
@endsection
