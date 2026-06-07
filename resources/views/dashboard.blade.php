@extends('layouts.auth')

@section('title', 'Podwórko')

@section('content')
    <h1 class="mb-4 text-xl font-semibold">Witaj w Podwórku</h1>
    <p class="mb-4 text-sm text-zinc-500">
        Nie należysz jeszcze do żadnego osiedla. Tworzenie i dołączanie do osiedla pojawi się wkrótce.
    </p>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit"
                class="w-full rounded-lg bg-zinc-900 px-3 py-2.5 text-base text-white hover:bg-zinc-800">
            Wyloguj się
        </button>
    </form>
@endsection
