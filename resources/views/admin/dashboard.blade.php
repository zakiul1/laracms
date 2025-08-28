@extends('admin.layout', ['title' => 'Dashboard'])

@section('content')
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-radius border border-outline bg-surface p-4 dark:border-outline-dark dark:bg-surface-dark">
            <h3 class="font-semibold mb-2">Welcome</h3>
            <p class="text-sm opacity-80">You’re in the Penguin UI admin.</p>
        </div>
        {{-- add your cards… --}}
    </div>
@endsection
