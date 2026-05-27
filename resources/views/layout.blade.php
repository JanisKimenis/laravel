<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Bibliotēka')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-blue-600 text-white p-4 shadow">
        <div class="max-w-6xl mx-auto flex items-center gap-6">
            <a href="{{ route('books.index') }}" class="text-lg font-bold">Bibliotēka</a>
            <a href="{{ route('books.index') }}" class="hover:underline">Grāmatas</a>
            <a href="{{ route('readers.index') }}" class="hover:underline">Lasītāji</a>
            <a href="{{ route('loans.index') }}" class="hover:underline">Aizņēmumi</a>
            <a href="{{ route('loans.overdue') }}" class="hover:underline">Kavētie</a>
            <a href="{{ route('books.journal') }}" class="hover:underline">Žurnāls</a>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto p-6">
        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
