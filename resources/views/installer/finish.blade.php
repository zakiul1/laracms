@extends('installer.layout')

@section('content')
    <h2 class="text-xl font-semibold mb-4">Finalize Installation</h2>
    <p class="text-sm text-gray-700 mb-4">
        We will run database migrations, create your admin account and save site settings.
    </p>

    <form method="POST" action="{{ route('install.finish.run') }}">
        @csrf
        <button type="submit" class="px-4 py-2 rounded-lg bg-black text-white">Finish Installation</button>
    </form>
@endsection
