@extends('layouts.auth')

@section('title', 'Podwórko')

@section('content')
    <h1 class="mb-4 text-xl font-semibold">Twoje osiedla</h1>

    @if (session('status'))
        <p class="mb-4 rounded-lg bg-emerald-50 px-3 py-2.5 text-sm text-emerald-800" role="status">{{ session('status') }}</p>
    @endif

    @forelse ($neighbourhoods as $neighbourhood)
        <div class="mb-2 flex items-center justify-between rounded-lg border border-zinc-200 px-3 py-2.5">
            <span class="text-sm font-medium">{{ $neighbourhood->name }}</span>
            <span class="font-mono text-sm tracking-widest text-zinc-500">{{ $neighbourhood->access_code }}</span>
        </div>
    @empty
        <p class="mb-4 text-sm text-zinc-500">Nie należysz jeszcze do żadnego osiedla.</p>
    @endforelse

    <a href="{{ route('neighbourhoods.create') }}"
       class="mt-4 block w-full rounded-lg bg-zinc-900 px-3 py-2.5 text-center text-base text-white hover:bg-zinc-800">
        Utwórz osiedle
    </a>

    <form method="POST" action="{{ route('logout') }}" class="mt-3">
        @csrf
        <button type="submit"
                class="w-full rounded-lg border border-zinc-300 px-3 py-2.5 text-base hover:bg-zinc-50">
            Wyloguj się
        </button>
    </form>
@endsection
