@extends('layouts.auth')

@section('title', 'Logowanie — Podwórko')

@section('content')
    <h1 class="mb-4 text-xl font-semibold">Zaloguj się do Podwórka</h1>

    @error('email')
        <p class="mb-4 rounded-lg bg-red-50 px-3 py-2.5 text-sm text-red-800" role="alert">{{ $message }}</p>
    @enderror

    <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
        @csrf
        <div>
            <label for="email" class="mb-1.5 block text-sm">Adres email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}"
                   required autofocus inputmode="email" autocomplete="email"
                   class="w-full rounded-lg border border-zinc-300 px-3 py-2.5 text-base focus:border-zinc-900 focus:outline-none">
        </div>
        <button type="submit"
                class="w-full rounded-lg bg-zinc-900 px-3 py-2.5 text-base text-white hover:bg-zinc-800">
            Wyślij link do logowania
        </button>
    </form>
@endsection
