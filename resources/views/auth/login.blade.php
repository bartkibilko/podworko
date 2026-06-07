@extends('layouts.auth')

@section('title', 'Logowanie — Podwórko')

@section('content')
    <h1>Zaloguj się do Podwórka</h1>

    @error('email')
        <p class="msg msg-error" role="alert">{{ $message }}</p>
    @enderror

    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <label for="email">Adres email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}"
               required autofocus inputmode="email" autocomplete="email">
        <button type="submit">Wyślij link do logowania</button>
    </form>
@endsection
