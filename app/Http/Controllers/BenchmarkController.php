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
        $matchCount = null;

        return view('benchmark.index', compact('withoutIndex', 'withIndex', 'explainBefore', 'explainAfter', 'usesIndex', 'matchCount'));
    }

    public function run(): View
    {
        $matchCount = Book::where('title', 'LIKE', 'Booket%')->count();

        // Drop index for "without" test
        try {
            DB::statement('DROP INDEX IF EXISTS books_title_index');
        } catch (\Exception $e) {
        }

        $query = "SELECT * FROM books WHERE title >= 'Booket' AND title < 'Bookf'";

        $start = microtime(true);
        DB::select($query);
        $withoutIndex = round((microtime(true) - $start) * 1000, 2);

        $explainBefore = DB::select('EXPLAIN QUERY PLAN ' . $query);

        // Create index and retest
        DB::statement('CREATE INDEX IF NOT EXISTS books_title_index ON books(title)');

        $start = microtime(true);
        DB::select($query);
        $withIndex = round((microtime(true) - $start) * 1000, 2);

        $explainAfter = DB::select('EXPLAIN QUERY PLAN ' . $query);

        $usesIndex = false;
        foreach ($explainAfter as $row) {
            if (str_contains($row->detail, 'books_title_index')) {
                $usesIndex = true;
                break;
            }
        }

        return view('benchmark.index', compact('withoutIndex', 'withIndex', 'explainBefore', 'explainAfter', 'usesIndex', 'matchCount'));
    }
}
