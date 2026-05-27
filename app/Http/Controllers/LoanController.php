<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Reader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LoanController extends Controller
{
    public function index(): View
    {
        $loans = Loan::with(['book', 'reader'])->latest()->paginate(10);
        return view('loans.index', compact('loans'));
    }

    public function create(): View
    {
        $books = Book::available()->get();
        $readers = Reader::all();
        return view('loans.create', compact('books', 'readers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'book_id' => 'required|exists:books,id',
            'reader_id' => 'required|exists:readers,id',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $book = Book::where('id', $validated['book_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($book->available_copies < 1) {
                    throw new \Exception('Grāmata nav pieejama!');
                }

                $book->decrement('available_copies');

                Loan::create([
                    'book_id' => $book->id,
                    'reader_id' => $validated['reader_id'],
                    'borrowed_at' => now(),
                ]);
            });
        } catch (\Exception $e) {
            return redirect()->route('loans.create')
                ->with('error', 'Grāmata nav pieejama!');
        }

        return redirect()->route('loans.index')
            ->with('success', 'Grāmata izsniegta!');
    }

    public function returnBook(Loan $loan): RedirectResponse
    {
        try {
            DB::transaction(function () use ($loan) {
                $loan = Loan::where('id', $loan->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($loan->returned_at !== null) {
                    throw new \Exception('Grāmata jau atgriezta!');
                }

                $loan->update(['returned_at' => now()]);
                $loan->book()->increment('available_copies');
            });
        } catch (\Exception $e) {
            return redirect()->route('loans.index')
                ->with('error', 'Grāmata jau atgriezta!');
        }

        return redirect()->route('loans.index')->with('success', 'Grāmata atgriezta!');
    }

    public function overdue(): View
    {
        $loans = DB::table('overdue_loans')->get();

        return view('loans.overdue', compact('loans'));
    }
}
