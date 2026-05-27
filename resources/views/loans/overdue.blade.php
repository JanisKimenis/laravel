@extends('layout')
@section('title', 'Kavētie aizņēmumi')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Kavētie aizņēmumi</h1>
    <a href="{{ route('loans.index') }}" class="text-blue-600 hover:underline">Visi aizņēmumi</a>
</div>

<table class="w-full bg-white rounded shadow">
    <thead class="bg-gray-200">
        <tr>
            <th class="p-2 text-left">Grāmata</th>
            <th class="p-2 text-left">Lasītājs</th>
            <th class="p-2 text-left">E-pasts</th>
            <th class="p-2 text-left">Izsniegts</th>
            <th class="p-2 text-left">Kavējums (dienas)</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($loans as $loan)
        <tr class="border-t hover:bg-gray-50">
            <td class="p-2">{{ $loan->book_title }}</td>
            <td class="p-2">{{ $loan->reader_name }}</td>
            <td class="p-2">{{ $loan->reader_email }}</td>
            <td class="p-2">{{ \Carbon\Carbon::parse($loan->borrowed_at)->format('d.m.Y H:i') }}</td>
            <td class="p-2 text-red-600 font-semibold">{{ round($loan->days_overdue) }} d.</td>
        </tr>
        @empty
        <tr>
            <td colspan="5" class="p-4 text-center text-gray-500">Nav kavētu aizņēmumu.</td>
        </tr>
        @endforelse
    </tbody>
</table>
@endsection
