@extends('layout')
@section('title', 'Indeksu tests')

@section('content')
<h1 class="text-2xl font-bold mb-4">Indeksu testēšana — meklēšanas ātrums</h1>

<p class="mb-4 text-gray-600">
    Testa vaicājums: <code class="bg-gray-200 px-2 py-1 rounded">SELECT * FROM books WHERE title LIKE '%et%'</code>
</p>

@if ($withoutIndex === null)
    <form method="POST" action="{{ route('benchmark.run') }}">
        @csrf
        <p class="mb-4">Grāmatu skaits: <strong>{{ number_format(\App\Models\Book::count()) }}</strong></p>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Palaist testu
        </button>
    </form>
@else
    <div class="grid grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded shadow p-4">
            <h2 class="text-lg font-semibold mb-2">Bez indeksa</h2>
            <p class="text-3xl font-bold text-gray-600">{{ $withoutIndex }} <span class="text-base font-normal">ms</span></p>
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

    <h2 class="text-lg font-semibold mb-2">EXPLAIN QUERY PLAN (ar indeksu)</h2>
    <table class="w-full bg-white rounded shadow mb-4">
        <thead class="bg-gray-200">
            <tr>
                <th class="p-2 text-left">id</th>
                <th class="p-2 text-left">vecāks</th>
                <th class="p-2 text-left">nepieciešams</th>
                <th class="p-2 text-left">Detāļas</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($explain as $row)
            <tr class="border-t">
                <td class="p-2">{{ $row->id }}</td>
                <td class="p-2">{{ $row->parent }}</td>
                <td class="p-2">{{ $row->notused ?? '' }}</td>
                <td class="p-2 text-green-700">{{ $row->detail }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if (isset($explain[0]))
        @php $usesIndex = str_contains($explain[0]->detail ?? '', 'books_title_index') @endphp
        <div class="{{ $usesIndex ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700' }} border px-4 py-3 rounded mb-4">
            @if ($usesIndex)
                ✅ Datubāze izmanto indeksu <strong>books_title_index</strong> — netiek skenēta visa tabula.
            @else
                ❌ Indekss netiek izmantots.
            @endif
        </div>
    @endif

    <a href="{{ route('benchmark.index') }}" class="text-blue-600 hover:underline">Testēt vēlreiz</a>
@endif
@endsection
