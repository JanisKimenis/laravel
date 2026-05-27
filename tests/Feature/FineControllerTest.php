<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Reader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FineControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_fine_index_displays_form(): void
    {
        Reader::factory()->create(['name' => 'Testa Lasītājs']);

        $this->get(route('fines.index'))
            ->assertOk()
            ->assertSee('Soda aprēķins')
            ->assertSee('Testa Lasītājs');
    }

    public function test_fine_calculates_correct_amount(): void
    {
        $reader = Reader::factory()->create();
        $book = Book::factory()->create(['available_copies' => 1]);

        $loan = Loan::factory()->create([
            'reader_id' => $reader->id,
            'book_id' => $book->id,
            'borrowed_at' => now()->subDays(30),
            'returned_at' => null,
        ]);

        $this->get(route('fines.calculate', [
            'reader_id' => $reader->id,
            'rate' => 0.50,
        ]))->assertOk()
            ->assertSee('0.50 EUR/dienā')
            ->assertSee('EUR');
    }

    public function test_fine_returns_zero_for_no_overdue_loans(): void
    {
        $reader = Reader::factory()->create();

        $this->get(route('fines.calculate', [
            'reader_id' => $reader->id,
            'rate' => 0.50,
        ]))->assertOk()
            ->assertSee('0.00 EUR');
    }
}
