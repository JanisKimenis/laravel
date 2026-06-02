<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Journal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('q');
        $books = Book::search($search)->orderBy('created_at', 'desc')->paginate(10);
        return view('books.index', compact('books', 'search'));
    }

    public function create(): View
    {
        return view('books.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'isbn' => 'required|string|max:20|unique:books',
            'available_copies' => 'required|integer|min:0',
        ]);

        Book::create($validated);

        return redirect()->route('books.index')->with('success', 'Grāmata pievienota!');
    }

    public function show(string $id): View
    {
        $book = Book::withTrashed()->findOrFail($id);
        return view('books.show', compact('book'));
    }

    public function edit(Book $book): View
    {
        return view('books.edit', compact('book'));
    }

    public function update(Request $request, Book $book): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'isbn' => 'required|string|max:20|unique:books,isbn,' . $book->id . ',id',
            'available_copies' => 'required|integer|min:0',
        ]);

        $book->update($validated);

        return redirect()->route('books.index')->with('success', 'Grāmata atjaunota!');
    }

    public function destroy(Book $book): RedirectResponse
    {
        $book->delete();

        return redirect()->route('books.index')->with('success', 'Grāmata dzēsta!');
    }

    public function copy(Book $book): RedirectResponse
    {
        $copy = Book::create([
            'title' => "Copy of {$book->title}",
            'isbn' => 'COPY-' . strtoupper(uniqid()),
            'available_copies' => $book->available_copies,
            'copied_from_id' => $book->id,
        ]);

        return redirect()->route('books.show', $copy)->with('success', 'Grāmatas kopija izveidota!');
    }

    public function journal(): View
    {
        $entries = Journal::with('book')->orderBy('created_at', 'desc')->paginate(20);

        return view('books.journal', compact('entries'));
    }
}
