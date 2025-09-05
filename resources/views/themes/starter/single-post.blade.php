@extends("themes.$activeTheme.layout")

@section('title', $post->title ?? 'Post')

@section('content')
    <article class="mx-auto max-w-3xl px-4 py-10">
        <h1 class="text-3xl font-bold">{{ $post->title }}</h1>

        <div class="mt-2 text-sm text-neutral-500">
            @if ($post->published_at)
                Published {{ $post->published_at->format('M d, Y') }}
            @endif
            @if ($post->author?->name)
                · by {{ $post->author->name }}
            @endif
        </div>

        {{-- Featured gallery (role=featured) --}}
        @php $gallery = $post->galleryFeatured ?? collect(); @endphp
        @if (count($gallery))
            <div class="mt-6 grid gap-4 md:grid-cols-2">
                @foreach ($gallery as $m)
                    <figure class="overflow-hidden rounded-xl border">
                        <img src="{{ $m->url ?? ($m->thumb ?? '') }}" alt="{{ $m->alt ?? '' }}"
                            class="w-full h-64 object-cover">
                    </figure>
                @endforeach
            </div>
        @endif

        <div class="prose prose-neutral max-w-none mt-8">
            {!! $post->content !!}
        </div>

        {{-- Tags/Categories (optional, only if you pass them) --}}
        @if (!empty($categories) || !empty($tags))
            <div class="mt-8 flex flex-wrap gap-3 text-sm">
                @if (!empty($categories))
                    <div>
                        <span class="font-medium">Categories:</span>
                        @foreach ($categories as $c)
                            <a href="{{ url('/c/' . $c['slug']) }}"
                                class="text-primary hover:underline">{{ $c['name'] }}</a>
                            @if (!$loop->last)
                                ,
                            @endif
                        @endforeach
                    </div>
                @endif
                @if (!empty($tags))
                    <div>
                        <span class="font-medium">Tags:</span>
                        @foreach ($tags as $t)
                            <a href="{{ url('/t/' . $t['slug']) }}"
                                class="text-primary hover:underline">{{ $t['name'] }}</a>
                            @if (!$loop->last)
                                ,
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </article>
@endsection
