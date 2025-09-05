@extends("themes.$activeTheme.layout")

@section('title', 'Not Found')

@section('content')
    <section class="mx-auto max-w-3xl px-4 py-24 text-center">
        <h1 class="text-5xl font-black">404</h1>
        <p class="mt-3 text-neutral-600">The page you’re looking for doesn’t exist.</p>
        <a href="{{ url('/') }}" class="mt-6 inline-block rounded-lg bg-primary px-4 py-2 text-white">Back to Home</a>
    </section>
@endsection
