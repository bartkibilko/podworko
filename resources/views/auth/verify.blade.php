@extends('layouts.auth')

@section('title', 'Potwierdź logowanie — Podwórko')

@section('content')
    <h1 class="mb-4 text-xl font-semibold">Potwierdź logowanie</h1>
    <p class="mb-4 text-sm text-zinc-500">Kliknij poniżej, aby dokończyć logowanie do Podwórka.</p>
    <form method="POST" action="{{ route('login.verify.store') }}">
        @csrf
        <input type="hidden" name="email" value="{{ $email }}">
        <input type="hidden" name="token" value="{{ $token }}">
        <button type="submit"
                class="w-full rounded-lg bg-zinc-900 px-3 py-2.5 text-base text-white hover:bg-zinc-800">
            Zaloguj się
        </button>
    </form>
@endsection
