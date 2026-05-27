@extends('layout')
@section('title', 'Soda likme')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Soda likme</h1>
    <a href="{{ route('loans.overdue') }}" class="text-blue-600 hover:underline">Kavētie aizņēmumi</a>
</div>

<div class="bg-white rounded shadow p-6 max-w-lg">
    <p class="text-gray-600 mb-4">
        Šī vērtība tiek glabāta datubāzē un izmantota soda aprēķināšanai
        kavēto aizņēmumu lapā. Lai mainītu sodu, vienkārši izmainiet šo vērtību.
    </p>

    <form method="POST" action="{{ route('fines.update') }}">
        @csrf
        <div class="mb-4">
            <label class="block text-gray-700 mb-1">Soda likme (EUR/dienā)</label>
            <input type="number" name="rate" step="0.01" min="0" max="100" value="{{ number_format((float) $rate, 2) }}" required
                   class="w-full border rounded p-2 text-lg @error('rate') border-red-500 @enderror">
            @error('rate') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Saglabāt</button>
    </form>
</div>
@endsection
