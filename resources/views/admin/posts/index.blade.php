@extends('admin.layout', ['title' => $type === 'page' ? 'Pages' : 'Posts'])

@section('content')
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">
            All {{ $type === 'page' ? 'Pages' : 'Posts' }}
        </h1>

        @php
            $createRoute = $type === 'page' ? 'admin.pages.create' : 'admin.posts.create';
            $showCategories = $type === 'post';
        @endphp

        <a href="{{ route($createRoute) }}" class="px-3 py-2 rounded-radius bg-primary text-white text-sm">
            + New {{ $type === 'page' ? 'Page' : 'Post' }}
        </a>
    </div>

    <div class="rounded-radius border border-outline dark:border-outline-dark overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-surface-alt dark:bg-surface-dark-alt">
                <tr>
                    <th class="p-3 text-left">Title</th>
                    @if ($showCategories)
                        <th class="p-3 text-left w-56">Categories</th>
                    @endif
                    <th class="p-3 text-left w-32">Status</th>
                    <th class="p-3 text-left w-44">Author</th>
                    <th class="p-3 text-left w-40">Published</th>
                    <th class="p-3 w-44"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $post)
                    <tr class="border-t border-outline dark:border-outline-dark">
                        <td class="p-3">
                            <a class="underline-offset-2 hover:underline"
                                href="{{ route($type === 'page' ? 'admin.pages.edit' : 'admin.posts.edit', $post) }}">
                                {{ $post->title }}
                            </a>
                            <div class="text-[11px] opacity-60">/{{ $post->slug }}</div>
                        </td>

                        @if ($showCategories)
                            @php
                                // Try to collect category names from any available relation;
                                // fall back to a tiny join if not present.
                                $catNames = [];
                                try {
                                    // If you have a terms/relations eager-loaded
                                    if (isset($post->terms)) {
                                        foreach ($post->terms as $t) {
                                            // support shapes: $t->taxonomy + $t->term, or just $t->name
                                            $taxonomy = $t->taxonomy ?? ($t->pivot->taxonomy ?? null);
                                            if ($taxonomy === 'category') {
                                                $catNames[] = $t->name ?? ($t->term->name ?? null);
                                            }
                                        }
                                        $catNames = array_filter($catNames);
                                    }

                                    if (empty($catNames)) {
                                        $catNames = \Illuminate\Support\Facades\DB::table('term_relationships as tr')
                                            ->join('term_taxonomies as tt', 'tt.id', '=', 'tr.term_taxonomy_id')
                                            ->join('terms as t', 't.id', '=', 'tt.term_id')
                                            ->where('tr.object_id', $post->id)
                                            ->where('tt.taxonomy', 'category')
                                            ->pluck('t.name')
                                            ->toArray();
                                    }
                                } catch (\Throwable $e) {
                                    $catNames = [];
                                }
                                $catsLabel = $catNames ? implode(', ', $catNames) : 'Uncategorized';
                            @endphp
                            <td class="p-3">{{ $catsLabel }}</td>
                        @endif

                        <td class="p-3">{{ ucfirst($post->status) }}</td>
                        <td class="p-3">{{ optional($post->author)->name ?? '—' }}</td>
                        <td class="p-3">{{ $post->published_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="p-3">
                            <div class="flex items-center gap-3">
                                <a class="text-blue-600 hover:underline"
                                    href="{{ route($type === 'page' ? 'admin.pages.edit' : 'admin.posts.edit', $post) }}">
                                    Edit
                                </a>

                                @php $delId = 'del-'.$type.'-'.$post->id; @endphp
                                <form id="{{ $delId }}" method="POST"
                                    action="{{ route($type === 'page' ? 'admin.pages.destroy' : 'admin.posts.destroy', $post) }}"
                                    class="hidden">
                                    @csrf @method('DELETE')
                                </form>

                                <button type="button" class="text-red-600 hover:underline"
                                    @click="window.dispatchEvent(new CustomEvent('confirm', {
                                            detail: {
                                                message: 'Delete this {{ $type === 'page' ? 'page' : 'post' }}?',
                                                submit: '{{ $delId }}'
                                            }
                                        }))">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $showCategories ? 6 : 5 }}" class="p-6 text-center">
                            No {{ $type === 'page' ? 'pages' : 'posts' }} found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $items->links() }}
    </div>
@endsection
