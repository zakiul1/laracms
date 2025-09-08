<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>No Theme Active</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gray-50 text-gray-800">
    <div class="max-w-3xl mx-auto p-6">
        <div class="rounded-lg border bg-white p-6">
            <h1 class="text-2xl font-semibold mb-2">No Theme Active</h1>
            <p class="text-sm text-gray-600">Activate a theme from <strong>Admin → Appearance → Themes</strong>.</p>
        </div>
        <div class="mt-6">
            @yield('content')
        </div>
    </div>
</body>

</html>
