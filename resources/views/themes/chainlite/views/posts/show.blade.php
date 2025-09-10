@extends('theme::layouts.app', [
    'title' => optional($post->seo)->meta_title ?? $post->title,
    'seo' => optional($post->seo)->toArray() ?? [],
])

@section('content')
    <article class="cf-py-12">
        <div class="cf-wrap">
            <header>
                <h1 class="cf-text-3xl cf-font-bold">{{ $post->title }}</h1>
                @if ($fm = $post->spatieFeaturedMedia())
                    <figure class="cf-mt-4">
                        <img class="cf-w-full cf-rounded-xl" src="{{ $fm->getUrl('w1280_webp') }}" alt="{{ $post->title }}"
                            loading="lazy">
                    </figure>
                @endif
            </header>

            <div class="cf-prose cf-max-w-none cf-mt-6">
                {!! do_shortcode($post->content) !!}
            </div>
        </div>
    </article>
@endsection
