<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BenchmarkController extends Controller
{
    public function index(): View
    {
        $withoutIndex = null;
        $withIndex = null;
        $explain = null;

        return view('benchmark.index', compact('withoutIndex', 'withIndex', 'explain'));
    }

    public function run(): View
    {
        $query = 'SELECT * FROM books WHERE title LIKE ?';

        // === Search WITHOUT index ===
        $start = microtime(true);
        DB::select($query, ['%et%']);
        $withoutIndex = round((microtime(true) - $start) * 1000, 2);

        // Get EXPLAIN QUERY PLAN before index
        try {
            DB::statement('DROP INDEX IF EXISTS books_title_index');
        } catch (\Exception $e) {
        }

        // === Create index and re-test ===
        DB::statement('CREATE INDEX IF NOT EXISTS books_title_index ON books(title)');

        $start = microtime(true);
        DB::select($query, ['%et%']);
        $withIndex = round((microtime(true) - $start) * 1000, 2);

        // EXPLAIN QUERY PLAN
        $explain = DB::select('EXPLAIN QUERY PLAN ' . $query, ['%et%']);

        return view('benchmark.index', compact('withoutIndex', 'withIndex', 'explain'));
    }
}
