@extends('admin.layout', ['title' => 'Posts'])

@section('content')
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">All Posts</h1>
        <a href="{{ route('admin.posts.create') }}" class="px-3 py-2 rounded-radius bg-primary text-white text-sm">+ New
            Post</a>
    </div>

    @if (session('status'))
        <div class="mb-3 text-sm px-3 py-2 rounded bg-green-100 text-green-800">{{ session('status') }}</div>
    @endif

    <div class="rounded-radius border border-outline dark:border-outline-dark overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-surface-alt dark:bg-surface-dark-alt">
                <tr>
                    <th class="p-3 text-left">Title</th>
                    <th class="p-3 text-left w-40">Status</th>
                    <th class="p-3 text-left w-48">Author</th>
                    <th class="p-3 text-left w-40">Published</th>
                    <th class="p-3 w-36"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $post)
                    <tr class="border-t border-outline dark:border-outline-dark">
                        <td class="p-3">
                            <a class="underline-offset-2 hover:underline"
                                href="{{ route('admin.posts.edit', $post) }}">{{ $post->title }}</a>
                            <div class="text-[11px] opacity-60">/{{ $post->slug }}</div>
                        </td>
                        <td class="p-3">{{ ucfirst($post->status) }}</td>
                        <td class="p-3">{{ optional($post->author)->name ?? '—' }}</td>
                        <td class="p-3">{{ $post->published_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="p-3">
                            <form method="POST" action="{{ route('admin.posts.destroy', $post) }}"
                                onsubmit="return confirm('Delete this post?')">
                                @csrf @method('DELETE')
                                <button class="text-red-600 hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-6 text-center">No posts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $items->links() }}
    </div>
@endsection
