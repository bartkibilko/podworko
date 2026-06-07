<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Logowanie — Podwórko</title>
</head>
<body>
    <main>
        <h1>Zaloguj się do Podwórka</h1>

        @if (session('status'))
            <p role="status">{{ session('status') }}</p>
        @endif

        @error('email')
            <p role="alert">{{ $message }}</p>
        @enderror

        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <label for="email">Adres email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
            <button type="submit">Wyślij link do logowania</button>
        </form>
    </main>
</body>
</html>
