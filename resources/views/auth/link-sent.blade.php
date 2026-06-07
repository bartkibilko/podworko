@extends('layouts.auth')

@section('title', 'Link wysłany — Podwórko')

@section('content')
    <h1>Sprawdź skrzynkę</h1>
    <p class="msg msg-status" role="status">
        Jeśli ten adres może się zalogować, wysłaliśmy na niego link. Jest ważny 15 minut i działa tylko raz.
    </p>
    <p class="muted"><a href="{{ route('login') }}">Wróć do logowania</a></p>
@endsection
