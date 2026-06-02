@extends('layout')
@section('title', $book->title)

@section('content')
<div class="mb-4">
    <a href="{{ route('books.index') }}" class="text-blue-600 hover:underline">&larr; Atpakaļ uz sarakstu</a>
</div>

@if ($book->trashed())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        Šī grāmata ir dzēsta ({{ $book->deleted_at->format('d.m.Y H:i') }}).
    </div>
@endif

<div class="bg-white rounded shadow p-6">
    <h1 class="text-2xl font-bold mb-4">{{ $book->title }}</h1>

    <dl class="grid grid-cols-2 gap-4">
        <dt class="text-gray-500">ISBN</dt>
        <dd>{{ $book->isbn }}</dd>

        <dt class="text-gray-500">Pieejamie eksemplāri</dt>
        <dd>{{ $book->available_copies }}</dd>

        @if ($book->copiedFrom)
        <dt class="text-gray-500">Kopija no</dt>
        <dd><a href="{{ route('books.show', $book->copiedFrom) }}" class="text-blue-600 hover:underline">{{ $book->copiedFrom->title }}</a></dd>
        @endif

        <dt class="text-gray-500">Izveidots</dt>
        <dd>{{ $book->created_at->format('d.m.Y H:i') }}</dd>

        <dt class="text-gray-500">Pēdējoreiz atjaunots</dt>
        <dd>{{ $book->updated_at->format('d.m.Y H:i') }}</dd>
    </dl>

    <div class="mt-6 flex gap-2">
        <a href="{{ route('books.edit', $book) }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Labot</a>
        <form method="POST" action="{{ route('books.copy', $book) }}">
            @csrf
            <button type="submit" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">Kopēt</button>
        </form>
        <form method="POST" action="{{ route('books.destroy', $book) }}" onsubmit="return confirm('Dzēst?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Dzēst</button>
        </form>
    </div>
</div>
@endsection
