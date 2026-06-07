<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Podwórko')</title>
    {{-- Self-contained styles: mobile-first, usable from 320px. Tailwind/Vite
         can replace this once the asset build is wired into dev/deploy. --}}
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f4f4f5; color: #18181b; }
        .wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .card { width: 100%; max-width: 24rem; background: #fff; border: 1px solid #e4e4e7; border-radius: .75rem; padding: 1.5rem; }
        h1 { font-size: 1.25rem; line-height: 1.4; margin: 0 0 1rem; }
        label { display: block; font-size: .875rem; margin: 0 0 .375rem; }
        input[type=email] { width: 100%; padding: .625rem .75rem; border: 1px solid #d4d4d8; border-radius: .5rem; font-size: 1rem; }
        button { width: 100%; margin-top: 1rem; padding: .625rem .75rem; border: 0; border-radius: .5rem; background: #18181b; color: #fff; font-size: 1rem; cursor: pointer; }
        button:hover { background: #27272a; }
        .msg { padding: .625rem .75rem; border-radius: .5rem; font-size: .875rem; margin: 0 0 1rem; }
        .msg-status { background: #ecfdf5; color: #065f46; }
        .msg-error { background: #fef2f2; color: #991b1b; }
        .muted { color: #71717a; font-size: .875rem; }
        a { color: #18181b; }
    </style>
</head>
<body>
    <div class="wrap">
        <main class="card">
            @yield('content')
        </main>
    </div>
</body>
</html>
