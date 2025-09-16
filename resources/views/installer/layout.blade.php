<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Laracms Installer</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite('resources/css/app.css')
</head>

<body class="min-h-screen bg-gray-50 text-gray-900">
    <div class="max-w-3xl mx-auto py-10 px-4">
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-bold">Laracms Installer</h1>
            <p class="text-sm text-gray-500 mt-1">WordPress-style setup wizard</p>
        </div>

        <div class="bg-white rounded-2xl shadow p-6">
            @yield('content')
        </div>

        <div class="mt-6 text-center text-xs text-gray-500">
            &copy; {{ date('Y') }} Laracms
        </div>
    </div>
</body>

</html>
