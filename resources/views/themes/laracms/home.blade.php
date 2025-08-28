@extends("themes.$activeTheme.layout")

@section('content')
    <h1 style="font-size:24px;font-weight:700;margin-bottom:8px">Welcome to laracms</h1>
    <p>Phase 0 is running — modules, hooks, admin shell are ready.</p>
    @php do_action('home_after_intro'); @endphp
@endsection
