@extends("themes.$activeTheme.layout")

@section('title', $title ?? 'Home')

@section('content')
    <section class="mx-auto max-w-6xl px-4 py-10">
        @if (!empty($heroTitle) || !empty($heroText))
            <div class="mb-8 rounded-2xl bg-neutral-50 p-8">
                <h1 class="text-2xl font-bold">{{ $heroTitle ?? ($settings->site_title ?? 'Welcome') }}</h1>
                @if (!empty($heroText))
                    <p class="mt-2 text-neutral-600">{{ $heroText }}</p>
                @else
                    <p class="mt-2 text-neutral-600">{{ $settings->site_tagline ?? '' }}</p>
                @endif
            </div>
        @endif

        {{-- Posts loop (expects $posts = LengthAwarePaginator|Collection) --}}
        @php $list = $posts ?? collect(); @endphp

        <div class="grid gap-6 md:grid-cols-2">
            @forelse($list as $post)
                <article class="rounded-xl border border-neutral-200 p-5 hover:shadow-sm transition-shadow">
                    <h2 class="text-lg font-semibold">
                        <a href="{{ url('/p/' . $post->slug) }}" class="hover:underline">{{ $post->title }}</a>
                    </h2>
                    @if ($post->published_at)
                        <div class="mt-1 text-xs text-neutral-500">
                            {{ $post->published_at->format('M d, Y') }}
                        </div>
                    @endif
                    @if ($post->excerpt)
                        <p class="mt-3 text-sm text-neutral-700 line-clamp-3">
                            {{ is_array($post->excerpt) ? $post->excerpt['text'] ?? '' : $post->excerpt }}
                        </p>
                    @endif
                    <div class="mt-4">
                        <a href="{{ url('/p/' . $post->slug) }}" class="text-sm text-primary hover:underline">Read more
                            →</a>
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-xl border p-8 text-center text-neutral-600">
                    No posts yet.
                </div>
            @endforelse
        </div>

        @if (method_exists($list, 'links'))
            <div class="mt-8">
                {{ $list->links() }}
            </div>
        @endif
    </section>
@endsection
