@extends('layout')
@section('title', 'Aizņēmumi')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Aizņēmumi</h1>
    <a href="{{ route('loans.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Izsniegt grāmatu</a>
</div>

<table class="w-full bg-white rounded shadow">
    <thead class="bg-gray-200">
        <tr>
            <th class="p-2 text-left">Grāmata</th>
            <th class="p-2 text-left">Lasītājs</th>
            <th class="p-2 text-left">Izsniegšanas datums</th>
            <th class="p-2 text-left">Atgriešanas datums</th>
            <th class="p-2 text-left">Darbības</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($loans as $loan)
        <tr class="border-t hover:bg-gray-50">
            <td class="p-2">{{ $loan->book->title }}</td>
            <td class="p-2">{{ $loan->reader->name }}</td>
            <td class="p-2">{{ $loan->borrowed_at->format('d.m.Y H:i') }}</td>
            <td class="p-2">{{ $loan->returned_at ? $loan->returned_at->format('d.m.Y H:i') : '—' }}</td>
            <td class="p-2">
                @if (!$loan->returned_at)
                    <form method="POST" action="{{ route('loans.return', $loan) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="text-green-600 hover:underline">Atgriezt</button>
                    </form>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="mt-4">{{ $loans->links() }}</div>
@endsection
