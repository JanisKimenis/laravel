<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Reader;
use Illuminate\Database\Seeder;

class LoanSeeder extends Seeder
{
    public function run(): void
    {
        $books = Book::all();
        $readers = Reader::all();

        if ($books->isEmpty() || $readers->isEmpty()) {
            return;
        }

        foreach (range(1, 5) as $i) {
            Loan::factory()->create([
                'book_id' => $books->random()->id,
                'reader_id' => $readers->random()->id,
            ]);
        }
    }
}
