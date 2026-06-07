<!DOCTYPE html>
<html lang="pl" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Podwórko')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-zinc-100 text-zinc-900 antialiased">
    <div class="flex min-h-full items-center justify-center p-4">
        <main class="w-full max-w-sm rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
            @yield('content')
        </main>
    </div>
</body>
</html>
