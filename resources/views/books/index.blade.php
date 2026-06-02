@extends('layout')
@section('title', 'Grāmatas')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Grāmatas</h1>
    <a href="{{ route('books.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Pievienot grāmatu</a>
</div>

<form method="GET" action="{{ route('books.index') }}" class="mb-4 flex gap-2">
    <input type="text" name="q" value="{{ $search ?? '' }}" placeholder="Meklēt grāmatas..." class="border rounded px-3 py-2 flex-1">
    <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">Meklēt</button>
    @if (filled($search ?? null))
        <a href="{{ route('books.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">Notīrīt</a>
    @endif
</form>

<table class="w-full bg-white rounded shadow">
    <thead class="bg-gray-200">
        <tr>
            <th class="p-2 text-left">Nosaukums</th>
            <th class="p-2 text-left">ISBN</th>
            <th class="p-2 text-left">Pieejamie eksemplāri</th>
            <th class="p-2 text-left">Darbības</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($books as $book)
        <tr class="border-t hover:bg-gray-50">
            <td class="p-2"><a href="{{ route('books.show', $book) }}" class="text-blue-600 hover:underline">{{ $book->title }}</a></td>
            <td class="p-2">{{ $book->isbn }}</td>
            <td class="p-2">{{ $book->available_copies }}</td>
            <td class="p-2 flex gap-2">
                <a href="{{ route('books.edit', $book) }}" class="text-blue-600 hover:underline">Labot</a>
                <form method="POST" action="{{ route('books.destroy', $book) }}" onsubmit="return confirm('Dzēst?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-600 hover:underline">Dzēst</button>
                </form>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="mt-4">{{ $books->links() }}</div>
@endsection
