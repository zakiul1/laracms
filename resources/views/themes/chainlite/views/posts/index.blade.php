@php
    use App\Models\Post;
    $posts = $posts ?? Post::type('post')->where('status', 'published')->orderByDesc('published_at')->paginate(10);
@endphp

@extends('theme::layouts.app', ['title' => 'News'])

@section('content')
    <section class="cf-py-12">
        <div class="cf-wrap">
            <h1 class="cf-text-3xl cf-font-bold">News</h1>

            <div class="cf-mt-6 cf-space-y-6">
                @forelse($posts as $p)
                    <article class="cf-border cf-rounded-xl cf-p-5">
                        <h2 class="cf-text-2xl cf-font-semibold">
                            <a href="{{ url('/post/' . $p->slug) }}" class="hover:cf-text-sky-600">{{ $p->title }}</a>
                        </h2>
                        @if ($p->excerpt)
                            <p class="cf-text-slate-600 cf-mt-1">{{ $p->excerpt }}</p>
                        @endif
                        <a class="cf-inline-block cf-mt-2 cf-text-sky-600" href="{{ url('/post/' . $p->slug) }}">Read
                            more</a>
                    </article>
                @empty
                    <p>No posts yet.</p>
                @endforelse
            </div>

            <div class="cf-mt-8">{!! $posts->links() !!}</div>
        </div>
    </section>
@endsection
