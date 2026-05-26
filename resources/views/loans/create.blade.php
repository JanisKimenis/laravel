@extends('layout')
@section('title', 'Izsniegt grāmatu')

@section('content')
<h1 class="text-2xl font-bold mb-4">Izsniegt grāmatu</h1>

<form method="POST" action="{{ route('loans.store') }}" class="bg-white rounded shadow p-6 max-w-lg">
    @csrf

    <div class="mb-4">
        <label class="block text-gray-700 mb-1">Grāmata</label>
        <select name="book_id" class="w-full border rounded p-2 @error('book_id') border-red-500 @enderror">
            <option value="">— Izvēlies —</option>
            @foreach ($books as $book)
                <option value="{{ $book->id }}" {{ old('book_id') == $book->id ? 'selected' : '' }}>
                    {{ $book->title }} ({{ $book->available_copies }} pieejami)
                </option>
            @endforeach
        </select>
        @error('book_id') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="mb-4">
        <label class="block text-gray-700 mb-1">Lasītājs</label>
        <select name="reader_id" class="w-full border rounded p-2 @error('reader_id') border-red-500 @enderror">
            <option value="">— Izvēlies —</option>
            @foreach ($readers as $reader)
                <option value="{{ $reader->id }}" {{ old('reader_id') == $reader->id ? 'selected' : '' }}>
                    {{ $reader->name }} ({{ $reader->email }})
                </option>
            @endforeach
        </select>
        @error('reader_id') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Izsniegt</button>
    <a href="{{ route('loans.index') }}" class="ml-2 text-gray-600 hover:underline">Atcelt</a>
</form>
@endsection
