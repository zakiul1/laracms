@extends('theme::layouts.app', [
    'title' => optional($post->seo)->meta_title ?? $post->title,
    'seo' => optional($post->seo)->toArray() ?? [],
])

@section('content')
    <section class="cf-py-12">
        <div class="cf-wrap">
            <h1 class="cf-text-3xl cf-font-bold">{{ $post->title }}</h1>
            <div class="cf-prose cf-max-w-none cf-mt-4">
                {!! do_shortcode($post->content) !!}
            </div>
        </div>
    </section>
@endsection
