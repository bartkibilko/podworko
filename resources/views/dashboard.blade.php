@extends('layouts.auth')

@section('title', 'Podwórko')

@section('content')
    <h1>Witaj w Podwórku</h1>
    <p class="muted" style="margin-bottom:1rem;">
        Nie należysz jeszcze do żadnego osiedla. Tworzenie i dołączanie do osiedla pojawi się wkrótce.
    </p>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Wyloguj się</button>
    </form>
@endsection
