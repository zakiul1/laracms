<header>
    <div class="container" style="display:flex;justify-content:space-between;align-items:center;">
        <div class="brand">
            <img id="logo" src="" alt="" style="display:none">
            <span id="site-title">{{ config('app.name', 'Laravel') }}</span>
        </div>
        <nav>
            <a href="{{ url('/') }}">Home</a>
            <a href="{{ url('/pages') }}">Pages</a>
            <a href="{{ url('/contact') }}">Contact</a>
        </nav>
    </div>
</header>
