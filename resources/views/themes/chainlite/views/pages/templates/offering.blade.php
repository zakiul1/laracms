@extends('theme::layouts.app')

@section('title', 'Our Offering')

@section('content')
    @php
        use Illuminate\Support\Facades\DB;
        use Illuminate\Support\Str;
        use Illuminate\Support\Facades\Storage;

        // Fetch posts in category=product (published + public)
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
            // Eager load legacy + spatie (media is the Spatie relation)
            ->with(['featuredMedia', 'gallery', 'media'])
            ->orderByRaw('COALESCE(published_at, created_at) asc')
            ->get();

        // Optional ordering to match your visual layout
        $preferred = ['womenswear', 'menswear', 'bottoms', 'circular-knitwear', 'outerwear'];
        $offerings = $offerings
            ->sortBy(function ($p) use ($preferred) {
                $idx = array_search(Str::slug($p->title), $preferred, true);
                return $idx === false ? 999 : $idx;
            })
            ->values();

        // Responsive sizes tuned for your layout
        $sizes = '(min-width: 1280px) 560px, (min-width: 768px) 48vw, 100vw';
    @endphp

    {{-- Static hero --}}
    <section class="max-w-5xl mx-auto px-4 pt-16 pb-10 text-center">
        <h1 class="text-4xl md:text-5xl font-semibold tracking-tight text-slate-900">Our Offering</h1>
        <p class="mt-5 text-base md:text-lg text-slate-600 leading-relaxed max-w-3xl mx-auto">
            We're delighted to support the development of various types of Men's, Women's, and Kidswear, and our
            capabilities extend beyond what we showcase below. Don't hesitate to reach out for bespoke solutions
            tailored to your needs.
        </p>
    </section>

    @if ($offerings->isEmpty())
        <section class="max-w-4xl mx-auto px-4 pb-16">
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-6 text-center text-slate-600">
                No offerings found in the “product” category yet.
            </div>
        </section>
    @else
        {{-- Alternating image/text blocks --}}
        <section class="max-w-6xl mx-auto px-4 space-y-24 md:space-y-32">
            @foreach ($offerings as $i => $item)
                @php
                    // Prefer Spatie media via helper; else legacy
                    $spatie = $item->spatieFeaturedMedia();

                    $legacy = $item->featuredMedia ?? ($item->gallery->first() ?? null);
                    $legacyUrl = null;

                    if (!$spatie && $legacy) {
                        $disk = $legacy->disk ?? null;
                        $candidates = array_filter([
                            $legacy->url ?? null,
                            $legacy->path ?? null,
                            $legacy->file_path ?? null,
                            $legacy->filepath ?? null,
                            isset($legacy->dir, $legacy->filename)
                                ? trim($legacy->dir, '/') . '/' . $legacy->filename
                                : null,
                        ]);

                        foreach ($candidates as $cand) {
                            $cand = ltrim((string) $cand, '/');

                            // Absolute URL
                            if (preg_match('~^https?://~i', $cand)) {
                                $legacyUrl = $cand;
                                break;
                            }

                            // Disk-specific path
                            if ($disk && Storage::disk($disk)->exists($cand)) {
                                $legacyUrl = Storage::disk($disk)->url($cand);
                                break;
                            }

                            // Common disks
                            foreach (['public', 'local', 's3'] as $try) {
                                if (Storage::disk($try)->exists($cand)) {
                                    $legacyUrl = Storage::disk($try)->url($cand);
                                    break 2;
                                }
                            }

                            // Public path
                            if (is_file(public_path($cand))) {
                                $legacyUrl = asset($cand);
                                break;
                            }
                        }
                    }

                    // Alternate sides
                    $reverse = $i % 2 === 1;
                @endphp

                <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-center">
                    {{-- Image column --}}
                    <div class="{{ $reverse ? 'md:order-2' : '' }}">
                        @if ($spatie || $legacyUrl)
                            {{-- Use responsive component (prefers Spatie native srcset; falls back to conversions) --}}
                            <x-media.picture :media="$spatie" :src="$legacyUrl" conversion="w1280" :sizes="$sizes"
                                :alt="$item->title" class="w-full h-auto rounded-xl object-cover shadow-sm" />
                        @else
                            <div class="w-full aspect-[4/3] rounded-xl bg-gray-100 grid place-items-center text-gray-400">
                                No image
                            </div>
                        @endif
                    </div>

                    {{-- Text column --}}
                    <div class="max-w-[520px] mx-auto text-center {{ $reverse ? 'md:order-1' : '' }}">
                        <h2 class="text-2xl md:text-3xl font-medium mb-4 text-slate-900">{{ $item->title }}</h2>
                        <div class="text-slate-700 leading-7 text-sm md:text-base space-y-3">
                            {!! apply_filters('the_content', $item->content) !!}
                        </div>
                    </div>
                </div>
            @endforeach
        </section>
    @endif
@endsection
