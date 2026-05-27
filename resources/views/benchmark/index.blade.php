@extends('layout')
@section('title', 'Indeksu tests')

@section('content')
<h1 class="text-2xl font-bold mb-4">Indeksu testēšana — meklēšanas ātrums</h1>

@if ($withoutIndex === null)
    <form method="POST" action="{{ route('benchmark.run') }}">
        @csrf
        <p class="mb-4">Grāmatu skaits: <strong>{{ number_format(\App\Models\Book::count()) }}</strong></p>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Palaist testu
        </button>
    </form>
@else
    <p class="mb-4 text-gray-600">
        Testa vaicājums: <code class="bg-gray-200 px-2 py-1 rounded">SELECT * FROM books WHERE title >= 'Booket' AND title &lt; 'Bookf'</code>
        <br><span class="text-sm">(meklē visas grāmatas, kas sākas ar "Booket" — atrastas <strong>{{ number_format($matchCount) }}</strong> rindas)</span>
    </p>

    <div class="grid grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded shadow p-4">
            <h2 class="text-lg font-semibold mb-2">Bez indeksa</h2>
            <p class="text-3xl font-bold text-gray-600">{{ $withoutIndex }} <span class="text-base font-normal">ms</span></p>
            <p class="text-xs text-gray-400">pilns tabulas skenējums</p>
        </div>
        <div class="bg-white rounded shadow p-4">
            <h2 class="text-lg font-semibold mb-2">Ar indeksu</h2>
            <p class="text-3xl font-bold text-green-600">{{ $withIndex }} <span class="text-base font-normal">ms</span></p>
            @if ($withoutIndex > 0)
                <p class="text-sm text-gray-500">
                    {{ round(($withoutIndex - $withIndex) / $withoutIndex * 100) }}% ātrāk
                </p>
            @endif
        </div>
    </div>

    <h2 class="text-lg font-semibold mb-2">EXPLAIN QUERY PLAN — bez indeksa</h2>
    <table class="w-full bg-white rounded shadow mb-4">
        <thead class="bg-gray-200"><tr><th class="p-2 text-left">Detāļas</th></tr></thead>
        <tbody>
            @foreach ($explainBefore as $row)
            <tr class="border-t"><td class="p-2 text-red-700">{{ $row->detail }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <h2 class="text-lg font-semibold mb-2">EXPLAIN QUERY PLAN — ar indeksu</h2>
    <table class="w-full bg-white rounded shadow mb-4">
        <thead class="bg-gray-200"><tr><th class="p-2 text-left">Detāļas</th></tr></thead>
        <tbody>
            @foreach ($explainAfter as $row)
            <tr class="border-t"><td class="p-2 text-green-700">{{ $row->detail }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <div class="{{ $usesIndex ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700' }} border px-4 py-3 rounded mb-4">
        @if ($usesIndex)
            ✅ Datubāze izmanto indeksu <strong>books_title_index</strong> — netiek skenēta visa tabula.
        @else
            ❌ Indekss netiek izmantots.
        @endif
    </div>

    <a href="{{ route('benchmark.index') }}" class="text-blue-600 hover:underline">Testēt vēlreiz</a>
@endif
@endsection
