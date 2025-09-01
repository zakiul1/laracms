@php($mode = 'create') @php($post = new \App\Models\Post(['status' => 'draft']))
@include('admin.posts.form', compact('post', 'mode'))
