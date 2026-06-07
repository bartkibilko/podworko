@extends('layouts.auth')

@section('title', 'Link wysłany — Podwórko')

@section('content')
    <h1 class="mb-4 text-xl font-semibold">Sprawdź skrzynkę</h1>
    <p class="mb-4 rounded-lg bg-emerald-50 px-3 py-2.5 text-sm text-emerald-800" role="status">
        Wysłaliśmy link do logowania na podany adres. Jest ważny 15 minut i działa tylko raz.
    </p>
    <p class="text-sm text-zinc-500"><a href="{{ route('login') }}" class="underline">Wróć do logowania</a></p>
@endsection
