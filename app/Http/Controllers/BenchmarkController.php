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
        $driver = DB::connection()->getDriverName();
        $sampleTitle = Book::where('title', 'LIKE', 'Booket%')->first()->title;

        try {
            DB::statement('DROP INDEX IF EXISTS books_title_index');
        } catch (\Exception $e) {
        }

        // === WITHOUT index ===
        $start = microtime(true);
        DB::select('SELECT * FROM books WHERE title = ?', [$sampleTitle]);
        $withoutIndex = round((microtime(true) - $start) * 1000, 2);

        $explainBefore = $this->runExplain($sampleTitle);

        // === WITH index ===
        DB::statement('CREATE INDEX IF NOT EXISTS books_title_index ON books(title)');

        $start = microtime(true);
        DB::select('SELECT * FROM books WHERE title = ?', [$sampleTitle]);
        $withIndex = round((microtime(true) - $start) * 1000, 2);

        $explainAfter = $this->runExplain($sampleTitle);

        $usesIndex = false;
        foreach ($explainAfter as $row) {
            foreach ((array) $row as $col) {
                if (str_contains((string) $col, 'books_title_index') || str_contains((string) $col, 'Index Scan')) {
                    $usesIndex = true;
                    break 2;
                }
            }
        }

        return view('benchmark.index', compact('withoutIndex', 'withIndex', 'explainBefore', 'explainAfter', 'usesIndex', 'sampleTitle'));
    }

    private function runExplain(string $title): array
    {
        $driver = DB::connection()->getDriverName();
        $rows = $driver === 'sqlite'
            ? DB::select('EXPLAIN QUERY PLAN SELECT * FROM books WHERE title = ?', [$title])
            : DB::select('EXPLAIN (FORMAT TEXT) SELECT * FROM books WHERE title = ?', [$title]);

        return array_map(fn ($r) => (object) ['detail' => join(' ', array_values((array) $r))], $rows);
    }
}
