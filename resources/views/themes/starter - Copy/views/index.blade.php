<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Starter Theme</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="{{ $themeAsset('style.css') }}">
</head>

<body>
    <header>
        <h1>Starter Theme Active</h1>
        <nav>
            {{-- Example: print main menu if present --}}
            @if ($mainMenu)
                <ul>
                    @foreach ($mainMenu->items as $item)
                        <li>{{ $item->label }}</li>
                    @endforeach
                </ul>
            @endif
        </nav>
    </header>

    <main>
        <p>Replace this with your theme’s homepage template.</p>
    </main>
</body>

</html>
