@extends('theme::layouts.app')

@section('title', 'Our Offering')

@section('content')
    @php
        use Illuminate\Support\Facades\DB;

        // Only published posts in "product" category
        $offerings = \App\Models\Post::query()
            ->where('type', 'post')
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('term_relationships as tr')
                    ->join('term_taxonomies as tt', 'tt.id', '=', 'tr.term_taxonomy_id')
                    ->join('terms as t', 't.id', '=', 'tt.term_id')
                    ->whereColumn('tr.object_id', 'posts.id')
                    ->where('tt.taxonomy', 'category')
                    ->where('t.slug', 'product');
            })
            ->orderByRaw('COALESCE(published_at, created_at) asc')
            ->get();
    @endphp

    {{-- Hero --}}
    <section class="cf-container cf-mx-auto cf-max-w-6xl cf-px-4 cf-pt-16 cf-pb-10 cf-text-center">
        <h1 class="cf-text-4xl md:cf-text-5xl cf-font-semibold cf-tracking-tight">Our Offering</h1>
        <p class="cf-mt-5 cf-text-base md:cf-text-lg cf-text-gray-600">
            We're delighted to support the development of various types of Men's, Women's, and Kidswear, and our
            capabilities
            extend beyond what we showcase below. Don't hesitate to reach out for bespoke solutions tailored to your needs.
        </p>
    </section>

    {{-- Alternating blocks --}}
    <section class="cf-container cf-mx-auto cf-max-w-6xl cf-px-4 cf-space-y-20 md:cf-space-y-28">
        @foreach ($offerings as $i => $item)
            @php
                // Use Spatie media (synced in controllers) for the responsive component
                $media = method_exists($item, 'getFirstMedia') ? $item->getFirstMedia('images') : null;
                $odd = $i % 2 === 0; // first row: image left
                // Responsive sizes string similar to the design layout
                $sizes = '(min-width: 1024px) 560px, (min-width: 768px) 50vw, 100vw';
            @endphp

            <div class="cf-grid cf-grid-cols-1 md:cf-grid-cols-2 cf-gap-10 cf-items-center">
                {{-- Image --}}
                <div class="{{ $odd ? '' : 'md:cf-order-2' }}">
                    @if ($media)
                        <x-media.picture :media="$media" :sizes="$sizes"
                            class="cf-w-full cf-h-auto cf-rounded cf-object-cover cf-shadow-sm" />
                    @else
                        <div
                            class="cf-aspect-[4/3] cf-w-full cf-rounded cf-bg-gray-100 cf-grid cf-place-items-center cf-text-gray-400">
                            No image
                        </div>
                    @endif
                </div>

                {{-- Text --}}
                <div class="{{ $odd ? '' : 'md:cf-order-1' }}">
                    <h2 class="cf-text-2xl md:cf-text-3xl cf-font-medium cf-mb-4">{{ $item->title }}</h2>
                    <div class="prose max-w-none cf-text-gray-700">
                        {!! apply_filters('the_content', $item->content) !!}
                    </div>
                </div>
            </div>
        @endforeach
    </section>

    {{-- CTA --}}
    <section class="cf-container cf-mx-auto cf-max-w-6xl cf-px-4 cf-py-12 md:cf-py-16">
        <div class="cf-grid md:cf-grid-cols-2 cf-gap-8 cf-rounded cf-bg-[#BFD9F7] cf-p-6 md:cf-p-10">
            <div>
                <h3 class="cf-text-2xl md:cf-text-3xl cf-font-semibold">Interested in learning how we can help your
                    business?</h3>
                <p class="cf-mt-3 cf-text-gray-700">Get in touch via our contact form to schedule a discovery call.</p>
            </div>
            <div>
                <form action="#" method="post" class="cf-grid cf-grid-cols-1 md:cf-grid-cols-2 cf-gap-4">
                    @csrf
                    <div>
                        <label class="cf-block cf-text-sm cf-mb-1">Name*</label>
                        <input type="text" class="cf-w-full cf-border cf-rounded cf-px-3 cf-py-2"
                            placeholder="Fill in name">
                    </div>
                    <div>
                        <label class="cf-block cf-text-sm cf-mb-1">Email*</label>
                        <input type="email" class="cf-w-full cf-border cf-rounded cf-px-3 cf-py-2"
                            placeholder="Fill in email">
                    </div>
                    <div>
                        <label class="cf-block cf-text-sm cf-mb-1">Phone number</label>
                        <input type="text" class="cf-w-full cf-border cf-rounded cf-px-3 cf-py-2"
                            placeholder="Fill in phone number">
                    </div>
                    <div>
                        <label class="cf-block cf-text-sm cf-mb-1">Address</label>
                        <input type="text" class="cf-w-full cf-border cf-rounded cf-px-3 cf-py-2"
                            placeholder="Fill in address">
                    </div>
                    <div class="md:cf-col-span-2">
                        <label class="cf-block cf-text-sm cf-mb-1">Topic</label>
                        <input type="text" class="cf-w-full cf-border cf-rounded cf-px-3 cf-py-2"
                            placeholder="Type topic here">
                    </div>
                    <div class="md:cf-col-span-2">
                        <label class="cf-block cf-text-sm cf-mb-1">Message</label>
                        <textarea rows="4" class="cf-w-full cf-border cf-rounded cf-px-3 cf-py-2" placeholder="Type message here..."></textarea>
                    </div>
                    <div class="md:cf-col-span-2 cf-pt-2">
                        <button type="submit"
                            class="cf-w-full md:cf-w-auto cf-bg-black cf-text-white cf-px-6 cf-py-2 cf-rounded">
                            Send
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
