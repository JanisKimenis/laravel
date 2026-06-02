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
        $loans = Loan::with(['book', 'reader'])->orderBy('created_at', 'desc')->paginate(10);
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

        $book = Book::findOrFail($validated['book_id']);

        if ($book->available_copies < 1) {
            return redirect()->route('loans.create')
                ->with('error', 'Grāmata nav pieejama!');
        }

        $book->decrement('available_copies');

        Loan::create([
            'book_id' => $book->id,
            'reader_id' => $validated['reader_id'],
            'borrowed_at' => now(),
        ]);

        return redirect()->route('loans.index')
            ->with('success', 'Grāmata izsniegta!');
    }

    public function returnBook(Loan $loan): RedirectResponse
    {
        if ($loan->returned_at !== null) {
            return redirect()->route('loans.index')
                ->with('error', 'Grāmata jau atgriezta!');
        }

        $loan->update(['returned_at' => now()]);
        $loan->book()->increment('available_copies');

        return redirect()->route('loans.index')->with('success', 'Grāmata atgriezta!');
    }

    public function overdue(): View
    {
        $rate = DB::table('settings')->where('key', 'fine_per_day')->value('value') ?? '0.50';
        $loans = Loan::overdue()->with(['book', 'reader'])->get()->map(function ($loan) {
            return (object) [
                'loan_id' => $loan->id,
                'book_title' => $loan->book->title,
                'reader_name' => $loan->reader->name,
                'reader_email' => $loan->reader->email,
                'borrowed_at' => $loan->borrowed_at,
                'returned_at' => $loan->returned_at,
                'days_overdue' => now()->diffInDays($loan->borrowed_at),
            ];
        });

        return view('loans.overdue', compact('loans', 'rate'));
    }
}
