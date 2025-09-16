@extends('installer.layout')

@section('content')
    <h2 class="text-xl font-semibold mb-4">Requirements Check</h2>

    <ul class="space-y-2">
        @foreach ($checks as $label => $ok)
            <li class="flex items-center justify-between border rounded-lg p-3">
                <span>{{ $label }}</span>
                <span class="{{ $ok ? 'text-green-600' : 'text-red-600' }}">
                    {{ $ok ? 'Passed' : 'Failed' }}
                </span>
            </li>
        @endforeach
    </ul>

    <form method="POST" action="{{ route('install.requirements.next') }}" class="mt-6">
        @csrf
        <button type="submit" {{ $allPassed ? '' : 'disabled' }}
            class="px-4 py-2 rounded-lg text-white {{ $allPassed ? 'bg-black' : 'bg-gray-400 cursor-not-allowed' }}">
            Continue
        </button>
    </form>
@endsection
