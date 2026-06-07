@extends('layouts.auth')

@section('title', 'Kod osiedla — Podwórko')

@section('content')
    <h1 class="mb-1 text-xl font-semibold">{{ $name }}</h1>
    <p class="mb-4 text-sm text-zinc-500">Proponowany kod dostępu:</p>
    <p class="mb-2 text-center text-3xl font-bold tracking-widest">{{ $accessCode }}</p>
    <p class="mb-4 text-sm text-amber-700">Po zapisie kod nie może być zmieniony.</p>

    @error('access_code')
        <p class="mb-4 rounded-lg bg-red-50 px-3 py-2.5 text-sm text-red-800" role="alert">{{ $message }}</p>
    @enderror

    <form method="POST" action="{{ route('neighbourhoods.store') }}" class="mb-3">
        @csrf
        <input type="hidden" name="name" value="{{ $name }}">
        <input type="hidden" name="access_code" value="{{ $accessCode }}">
        <button type="submit"
                class="w-full rounded-lg bg-zinc-900 px-3 py-2.5 text-base text-white hover:bg-zinc-800">
            Zapisz osiedle
        </button>
    </form>

    <form method="POST" action="{{ route('neighbourhoods.preview') }}">
        @csrf
        <input type="hidden" name="name" value="{{ $name }}">
        <button type="submit"
                class="w-full rounded-lg border border-zinc-300 px-3 py-2.5 text-base hover:bg-zinc-50">
            Generuj nowy kod
        </button>
    </form>
@endsection
