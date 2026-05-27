<?php

namespace App\Http\Controllers;

use App\Models\Reader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FineController extends Controller
{
    public function index(): View
    {
        $readers = Reader::all();

        return view('fines.index', compact('readers'));
    }

    public function calculate(Request $request): View
    {
        $validated = $request->validate([
            'reader_id' => 'required|exists:readers,id',
            'rate' => 'required|numeric|min:0|max:100',
        ]);

        $reader = Reader::findOrFail($validated['reader_id']);
        $rate = (float) $validated['rate'];

        $result = DB::selectOne('
            SELECT
                COUNT(*) AS overdue_loans,
                COALESCE(SUM(julianday("now") - julianday(borrowed_at)), 0) AS total_days,
                COALESCE(SUM((julianday("now") - julianday(borrowed_at)) * ?), 0) AS total_fine
            FROM loans
            WHERE reader_id = ?
              AND returned_at IS NULL
              AND borrowed_at < datetime("now", "-14 days")
        ', [$rate, $reader->id]);

        $fine = round((float) $result->total_fine, 2);
        $days = round((float) $result->total_days);
        $count = (int) $result->overdue_loans;

        $readers = Reader::all();

        return view('fines.index', compact('readers', 'reader', 'rate', 'fine', 'days', 'count'));
    }
}
