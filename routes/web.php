<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\ReaderController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/books');

Route::resource('books', BookController::class);
Route::resource('readers', ReaderController::class);
Route::resource('loans', LoanController::class)->only(['index', 'create', 'store']);
Route::patch('loans/{loan}/return', [LoanController::class, 'returnBook'])->name('loans.return');
Route::get('loans/overdue', [LoanController::class, 'overdue'])->name('loans.overdue');
Route::get('books/journal', [BookController::class, 'journal'])->name('books.journal');
