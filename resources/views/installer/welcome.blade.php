@extends('installer.layout')

@section('content')
    <div class="space-y-4">
        <p>Welcome! This wizard will guide you through installing Laracms.</p>
        <div class="pt-4">
            <a href="{{ route('install.requirements') }}"
                class="inline-flex items-center px-4 py-2 rounded-lg bg-black text-white">Let’s Go</a>
        </div>
    </div>
@endsection
