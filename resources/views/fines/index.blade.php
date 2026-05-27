@extends('layout')
@section('title', 'Soda aprēķins')

@section('content')
<h1 class="text-2xl font-bold mb-4">Soda aprēķins par kavējumiem</h1>

<form method="GET" action="{{ route('fines.calculate') }}" class="bg-white rounded shadow p-6 max-w-lg mb-6">
    <div class="mb-4">
        <label class="block text-gray-700 mb-1">Lasītājs</label>
        <select name="reader_id" required class="w-full border rounded p-2">
            <option value="">— Izvēlies —</option>
            @foreach ($readers as $r)
                <option value="{{ $r->id }}" {{ isset($reader) && $reader->id == $r->id ? 'selected' : '' }}>
                    {{ $r->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="mb-4">
        <label class="block text-gray-700 mb-1">Soda likme (EUR/dienā)</label>
        <input type="number" name="rate" step="0.01" min="0" max="100" value="{{ $rate ?? 0.50 }}" required
               class="w-full border rounded p-2">
    </div>
    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Aprēķināt</button>
</form>

@if (isset($reader))
<div class="bg-white rounded shadow p-6 max-w-lg">
    <h2 class="text-lg font-semibold mb-2">Rezultāts</h2>
    <table class="w-full">
        <tr>
            <td class="py-1 text-gray-600">Lasītājs:</td>
            <td class="py-1 font-semibold">{{ $reader->name }}</td>
        </tr>
        <tr>
            <td class="py-1 text-gray-600">Kavētie aizņēmumi:</td>
            <td class="py-1">{{ $count }}</td>
        </tr>
        <tr>
            <td class="py-1 text-gray-600">Kopējais kavējums:</td>
            <td class="py-1">{{ $days }} dienas</td>
        </tr>
        <tr>
            <td class="py-1 text-gray-600">Soda likme:</td>
            <td class="py-1">{{ number_format($rate, 2) }} EUR/dienā</td>
        </tr>
        <tr class="border-t">
            <td class="py-2 text-gray-600 font-semibold">KOPĀ:</td>
            <td class="py-2 text-red-600 font-bold text-xl">{{ number_format($fine, 2) }} EUR</td>
        </tr>
    </table>
</div>
@endif
@endsection
