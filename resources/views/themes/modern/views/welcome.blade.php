@extends('theme::layouts.app')

@section('content')
    <section class="hero">
        <h1>Welcome to the Modern Theme</h1>
        <p class="muted">If you can see this, the “modern” theme is active.</p>
    </section>

    <section class="grid two">
        <article class="card">
            <h2>Pages & Posts</h2>
            <p>Build your content from the admin. This theme will render your standard pages and menus.</p>
        </article>

        <article class="card">
            <h2>Menus</h2>
            <p>Go to Appearance → Menus and assign a <strong>Header</strong> or <strong>Main</strong> menu.</p>
        </article>
    </section>
@endsection
