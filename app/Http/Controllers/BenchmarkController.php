<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BenchmarkController extends Controller
{
    public function index(): View
    {
        return view('benchmark.index', [
            'withoutIndex' => null,
            'withIndex' => null,
            'explainBefore' => null,
            'explainAfter' => null,
            'usesIndex' => false,
            'sampleTitle' => null,
        ]);
    }

    public function run(): View
    {
        $sampleTitle = Book::first()?->title ?? 'test';
        $driver = DB::connection()->getDriverName();

        // === WITHOUT index ===
        DB::statement('DROP INDEX IF EXISTS books_title_index');

        $start = microtime(true);
        Book::where('title', $sampleTitle)->first();
        $withoutIndex = round((microtime(true) - $start) * 1000, 2);

        $explainBefore = $driver === 'pgsql'
            ? DB::select("EXPLAIN (FORMAT JSON) SELECT * FROM books WHERE title = ?", [$sampleTitle])
            : DB::select('EXPLAIN QUERY PLAN SELECT * FROM books WHERE title = ?', [$sampleTitle]);

        // === WITH index ===
        DB::statement('CREATE INDEX IF NOT EXISTS books_title_index ON books (title)');

        $start = microtime(true);
        Book::where('title', $sampleTitle)->first();
        $withIndex = round((microtime(true) - $start) * 1000, 2);

        $explainAfter = $driver === 'pgsql'
            ? DB::select("EXPLAIN (FORMAT JSON) SELECT * FROM books WHERE title = ?", [$sampleTitle])
            : DB::select('EXPLAIN QUERY PLAN SELECT * FROM books WHERE title = ?', [$sampleTitle]);

        $planJson = json_encode($explainAfter);
        $usesIndex = str_contains($planJson, 'Index Scan')
            || str_contains($planJson, 'Index Only Scan')
            || str_contains($planJson, 'books_title_index');

        return view('benchmark.index', compact('withoutIndex', 'withIndex', 'explainBefore', 'explainAfter', 'usesIndex', 'sampleTitle'));
    }
}
