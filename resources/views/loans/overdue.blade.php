@extends('layout')
@section('title', 'Kavētie aizņēmumi')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Kavētie aizņēmumi</h1>
    <div class="flex gap-2">
        <span class="text-sm text-gray-500 self-center">Likme: {{ number_format((float) $rate, 2) }} EUR/dienā</span>
        <a href="{{ route('fines.index') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">Mainīt likmi</a>
        <a href="{{ route('loans.index') }}" class="text-blue-600 hover:underline self-center">Visi aizņēmumi</a>
    </div>
</div>

<table class="w-full bg-white rounded shadow">
    <thead class="bg-gray-200">
        <tr>
            <th class="p-2 text-left">Grāmata</th>
            <th class="p-2 text-left">Lasītājs</th>
            <th class="p-2 text-left">Izsniegts</th>
            <th class="p-2 text-left">Kavējums</th>
            <th class="p-2 text-left">Sods</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($loans as $loan)
        @php $fine = round($loan->days_overdue * (float) $rate, 2); @endphp
        <tr class="border-t hover:bg-gray-50">
            <td class="p-2">{{ $loan->book_title }}</td>
            <td class="p-2">{{ $loan->reader_name }} ({{ $loan->reader_email }})</td>
            <td class="p-2">{{ \Carbon\Carbon::parse($loan->borrowed_at)->format('d.m.Y') }}</td>
            <td class="p-2 text-red-600 font-semibold">{{ round($loan->days_overdue) }} d.</td>
            <td class="p-2 text-red-700 font-bold">{{ number_format($fine, 2) }} EUR</td>
        </tr>
        @empty
        <tr>
            <td colspan="5" class="p-4 text-center text-gray-500">Nav kavētu aizņēmumu.</td>
        </tr>
        @endforelse
    </tbody>
</table>
@endsection
