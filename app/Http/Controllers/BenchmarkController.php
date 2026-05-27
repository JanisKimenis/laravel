<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BenchmarkController extends Controller
{
    public function index(): View
    {
        $withoutIndex = null;
        $withIndex = null;
        $explainBefore = null;
        $explainAfter = null;
        $usesIndex = false;
        $totalBooks = null;
        $sampleTitle = null;

        return view('benchmark.index', compact('withoutIndex', 'withIndex', 'explainBefore', 'explainAfter', 'usesIndex', 'totalBooks', 'sampleTitle'));
    }

    public function run(): View
    {
        $totalBooks = Book::count();
        $sampleTitle = Book::where('title', 'LIKE', 'Booket%')->first()->title;

        // Drop index for "without" test
        try {
            DB::statement('DROP INDEX IF EXISTS books_title_index');
        } catch (\Exception $e) {
        }

        // === WITHOUT index ===
        $start = microtime(true);
        DB::select('SELECT * FROM books WHERE title = ?', [$sampleTitle]);
        $withoutIndex = round((microtime(true) - $start) * 1000, 2);

        $explainBefore = DB::select('EXPLAIN QUERY PLAN SELECT * FROM books WHERE title = ?', [$sampleTitle]);

        // === WITH index ===
        DB::statement('CREATE INDEX IF NOT EXISTS books_title_index ON books(title)');

        $start = microtime(true);
        DB::select('SELECT * FROM books WHERE title = ?', [$sampleTitle]);
        $withIndex = round((microtime(true) - $start) * 1000, 2);

        $explainAfter = DB::select('EXPLAIN QUERY PLAN SELECT * FROM books WHERE title = ?', [$sampleTitle]);

        $usesIndex = false;
        foreach ($explainAfter as $row) {
            if (str_contains($row->detail, 'books_title_index')) {
                $usesIndex = true;
                break;
            }
        }

        return view('benchmark.index', compact('withoutIndex', 'withIndex', 'explainBefore', 'explainAfter', 'usesIndex', 'totalBooks', 'sampleTitle'));
    }
}
