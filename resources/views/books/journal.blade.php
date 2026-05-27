@extends('layout')
@section('title', 'Žurnāls')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Žurnāls</h1>
    <a href="{{ route('books.index') }}" class="text-blue-600 hover:underline">Grāmatas</a>
</div>

<table class="w-full bg-white rounded shadow">
    <thead class="bg-gray-200">
        <tr>
            <th class="p-2 text-left">ID</th>
            <th class="p-2 text-left">Grāmata</th>
            <th class="p-2 text-left">Vecā vērtība</th>
            <th class="p-2 text-left">Jaunā vērtība</th>
            <th class="p-2 text-left">Izmainīts</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($entries as $entry)
        <tr class="border-t hover:bg-gray-50">
            <td class="p-2">{{ $entry->id }}</td>
            <td class="p-2">{{ $entry->book->title }}</td>
            <td class="p-2">{{ $entry->old_copies }}</td>
            <td class="p-2">{{ $entry->new_copies }}</td>
            <td class="p-2">{{ $entry->created_at }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="5" class="p-4 text-center text-gray-500">Žurnāls ir tukšs.</td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="mt-4">{{ $entries->links() }}</div>
@endsection
