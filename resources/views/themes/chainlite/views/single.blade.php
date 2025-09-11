@extends('theme::layouts.app')

@section('title', $post->seo->title ?? $post->title)

@section('content')
    <section class="cf-wrap cf-py-10">
        <h1 class="cf-text-3xl cf-font-semibold cf-mb-6">{{ $post->title }}</h1>
        <article class="prose max-w-none">
            {!! apply_filters('the_content', $post->content) !!}
        </article>
    </section>
@endsection
