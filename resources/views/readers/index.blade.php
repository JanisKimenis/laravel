@extends('layout')
@section('title', 'Lasītāji')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Lasītāji</h1>
    <a href="{{ route('readers.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Pievienot lasītāju</a>
</div>

<table class="w-full bg-white rounded shadow">
    <thead class="bg-gray-200">
        <tr>
            <th class="p-2 text-left">Vārds</th>
            <th class="p-2 text-left">E-pasts</th>
            <th class="p-2 text-left">Darbības</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($readers as $reader)
        <tr class="border-t hover:bg-gray-50">
            <td class="p-2">{{ $reader->name }}</td>
            <td class="p-2">{{ $reader->email }}</td>
            <td class="p-2 flex gap-2">
                <a href="{{ route('readers.edit', $reader) }}" class="text-blue-600 hover:underline">Labot</a>
                <form method="POST" action="{{ route('readers.destroy', $reader) }}" onsubmit="return confirm('Dzēst?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-600 hover:underline">Dzēst</button>
                </form>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="mt-4">{{ $readers->links() }}</div>
@endsection
