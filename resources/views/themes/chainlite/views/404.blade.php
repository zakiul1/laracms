@extends('theme::layouts.app', ['title' => 'Page not found'])

@section('content')
    <section class="cf-py-20">
        <div class="cf-wrap cf-text-center">
            <h1 class="cf-text-5xl cf-font-bold">404</h1>
            <p class="cf-mt-2 cf-text-slate-600">The page you’re looking for doesn’t exist.</p>
            <a class="cf-btn cf-mt-6" href="{{ url('/') }}">Back to home</a>
        </div>
    </section>
@endsection
