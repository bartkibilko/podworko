@extends('layouts.auth')

@section('title', 'Utwórz osiedle — Podwórko')

@section('content')
    <h1 class="mb-4 text-xl font-semibold">Utwórz osiedle</h1>

    @error('name')
        <p class="mb-4 rounded-lg bg-red-50 px-3 py-2.5 text-sm text-red-800" role="alert">{{ $message }}</p>
    @enderror

    <form method="POST" action="{{ route('neighbourhoods.preview') }}" class="space-y-4">
        @csrf
        <div>
            <label for="name" class="mb-1.5 block text-sm">Nazwa osiedla</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}"
                   required autofocus maxlength="100"
                   class="w-full rounded-lg border border-zinc-300 px-3 py-2.5 text-base focus:border-zinc-900 focus:outline-none">
        </div>
        <button type="submit"
                class="w-full rounded-lg bg-zinc-900 px-3 py-2.5 text-base text-white hover:bg-zinc-800">
            Dalej — wygeneruj kod
        </button>
    </form>

    <p class="mt-4 text-sm text-zinc-500"><a href="{{ route('dashboard') }}" class="underline">Wróć</a></p>
@endsection
