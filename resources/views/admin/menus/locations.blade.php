@extends('admin.layout', ['title' => 'Menu Locations'])

@section('content')
    <h1 class="text-xl font-semibold mb-4">Menu Locations</h1>

    @if (session('success'))
        <div class="mb-3 text-green-700 bg-green-50 border border-green-200 rounded p-2">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.menus.locations.update') }}" class="space-y-4">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($locations as $loc)
                <div class="border rounded-radius p-3">
                    <div class="font-medium">{{ $loc->name }} <span
                            class="text-xs text-muted-foreground">({{ $loc->slug }})</span></div>
                    <select name="assign[{{ $loc->slug }}]" class="mt-2 w-full border rounded-radius px-3 py-2">
                        <option value="">— None —</option>
                        @foreach ($menus as $m)
                            <option value="{{ $m->id }}" {{ $loc->menu_id === $m->id ? 'selected' : '' }}>
                                {{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach
        </div>
        <button class="px-3 py-2 border rounded-radius">Save</button>
    </form>
@endsection
