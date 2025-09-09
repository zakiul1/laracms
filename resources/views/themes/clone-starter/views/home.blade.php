@extends('theme::layouts.app')

@section('title', 'Welcome')

@section('hero')
    <h1>Clean starter hero</h1>
    <p>This hero will be re-styled to match your target site.</p>
    <a class="btn" href="#">Primary CTA</a>
@endsection

@section('content')
    <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px">
        @foreach (range(1, 6) as $i)
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:18px">
                <h3 style="margin:0 0 8px;font:600 18px/1.4 var(--font-heading),sans-serif">Card {{ $i }}</h3>
                <p style="margin:0 0 10px;color:#475569">We’ll map this section to your site’s cards/layout.</p>
                <a class="btn" href="#">Action</a>
            </div>
        @endforeach
    </div>
@endsection
