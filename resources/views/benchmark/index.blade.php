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
        Testa vaicājums: <code class="bg-gray-200 px-2 py-1 rounded">Book::where('title', '...')->first()</code>
        <br>Tiek meklēta grāmata: <strong>{{ $sampleTitle }}</strong>
    </p>

    <p class="text-sm text-gray-500 mb-2">Laiks 1 vaicājuma izpildei:</p>

    <div class="grid grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded shadow p-4">
            <h2 class="text-lg font-semibold mb-2">Bez indeksa</h2>
            <p class="text-3xl font-bold text-gray-600">{{ $withoutIndex }} <span class="text-base font-normal">ms</span></p>
            <p class="text-xs text-gray-400">pilns kolekcijas skenējums (COLLSCAN)</p>
        </div>
        <div class="bg-white rounded shadow p-4">
            <h2 class="text-lg font-semibold mb-2">Ar indeksu</h2>
            <p class="text-3xl font-bold text-green-600">{{ $withIndex }} <span class="text-base font-normal">ms</span></p>
            @if ($withoutIndex > 0)
                <p class="text-sm text-gray-500">
                    @php $pct = round(($withoutIndex - $withIndex) / $withoutIndex * 100); @endphp
                    @if ($pct > 0)
                        {{ $pct }}% ātrāk
                    @else
                        {{ abs($pct) }}% lēnāk
                    @endif
                </p>
            @endif
        </div>
    </div>

    <h2 class="text-lg font-semibold mb-2">EXPLAIN — bez indeksa</h2>
    <table class="w-full bg-white rounded shadow mb-4">
        <thead class="bg-gray-200"><tr><th class="p-2 text-left">Detāļas</th></tr></thead>
        <tbody>
            @foreach ($explainBefore ?? [] as $row)
            @php $content = is_string($row->{'QUERY PLAN'} ?? null) ? $row->{'QUERY PLAN'} : json_encode($row); @endphp
            <tr class="border-t"><td class="p-2 text-red-700 font-mono text-sm">{{ $content }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <h2 class="text-lg font-semibold mb-2">EXPLAIN — ar indeksu</h2>
    <table class="w-full bg-white rounded shadow mb-4">
        <thead class="bg-gray-200"><tr><th class="p-2 text-left">Detāļas</th></tr></thead>
        <tbody>
            @foreach ($explainAfter ?? [] as $row)
            @php $content = is_string($row->{'QUERY PLAN'} ?? null) ? $row->{'QUERY PLAN'} : json_encode($row); @endphp
            <tr class="border-t"><td class="p-2 text-green-700 font-mono text-sm">{{ $content }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <div class="{{ $usesIndex ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700' }} border px-4 py-3 rounded mb-4">
        @if ($usesIndex)
            ✅ MongoDB izmanto indeksu — indeksā atrod dokumentu uzreiz bez kolekcijas skenēšanas.
        @else
            ❌ Indekss netiek izmantots.
        @endif
    </div>

    <p class="text-sm text-gray-500 mt-4">
        <strong>Piezīme:</strong> Indekss palīdz tikai tad, ja vaicājums atrod nelielu daļu no dokumentiem (augsta selektivitāte).
    </p>

    <a href="{{ route('benchmark.index') }}" class="text-blue-600 hover:underline mt-4 inline-block">Testēt vēlreiz</a>
@endif
@endsection
