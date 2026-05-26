@extends('layout')
@section('title', 'Pievienot grāmatu')

@section('content')
<h1 class="text-2xl font-bold mb-4">Pievienot grāmatu</h1>

<form method="POST" action="{{ route('books.store') }}" class="bg-white rounded shadow p-6 max-w-lg">
    @csrf

    <div class="mb-4">
        <label class="block text-gray-700 mb-1">Nosaukums</label>
        <input type="text" name="title" value="{{ old('title') }}" class="w-full border rounded p-2 @error('title') border-red-500 @enderror">
        @error('title') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="mb-4">
        <label class="block text-gray-700 mb-1">ISBN</label>
        <input type="text" name="isbn" value="{{ old('isbn') }}" class="w-full border rounded p-2 @error('isbn') border-red-500 @enderror">
        @error('isbn') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="mb-4">
        <label class="block text-gray-700 mb-1">Pieejamie eksemplāri</label>
        <input type="number" name="available_copies" value="{{ old('available_copies', 1) }}" min="0" class="w-full border rounded p-2 @error('available_copies') border-red-500 @enderror">
        @error('available_copies') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Saglabāt</button>
    <a href="{{ route('books.index') }}" class="ml-2 text-gray-600 hover:underline">Atcelt</a>
</form>
@endsection
