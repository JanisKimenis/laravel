<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Reader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LoanControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_borrow_available_book(): void
    {
        $book = Book::factory()->create(['available_copies' => 2]);
        $reader = Reader::factory()->create();

        $this->post(route('loans.store'), [
            'book_id' => $book->id,
            'reader_id' => $reader->id,
        ])->assertRedirect(route('loans.index'));

        $this->assertDatabaseHas('loans', [
            'book_id' => $book->id,
            'reader_id' => $reader->id,
        ]);
        $this->assertEquals(1, $book->fresh()->available_copies);
    }

    public function test_cannot_borrow_unavailable_book(): void
    {
        $book = Book::factory()->create(['available_copies' => 0]);
        $reader = Reader::factory()->create();

        $this->post(route('loans.store'), [
            'book_id' => $book->id,
            'reader_id' => $reader->id,
        ])->assertRedirect(route('loans.create'))->assertSessionHas('error');
    }

    public function test_can_return_book(): void
    {
        $book = Book::factory()->create(['available_copies' => 0]);
        $loan = Loan::factory()->create([
            'book_id' => $book->id,
            'borrowed_at' => now(),
            'returned_at' => null,
        ]);

        $this->patch(route('loans.return', $loan))
            ->assertRedirect(route('loans.index'));

        $this->assertNotNull($loan->fresh()->returned_at);
        $this->assertEquals(1, $book->fresh()->available_copies);
    }

    public function test_transaction_prevents_double_borrowing(): void
    {
        $book = Book::factory()->create(['available_copies' => 1]);
        $reader1 = Reader::factory()->create();
        $reader2 = Reader::factory()->create();

        $this->post(route('loans.store'), [
            'book_id' => $book->id,
            'reader_id' => $reader1->id,
        ])->assertRedirect(route('loans.index'));

        $this->post(route('loans.store'), [
            'book_id' => $book->id,
            'reader_id' => $reader2->id,
        ])->assertRedirect(route('loans.create'))->assertSessionHas('error');

        $this->assertEquals(1, Loan::count());
        $this->assertEquals(0, $book->fresh()->available_copies);
    }

    public function test_cannot_return_book_twice(): void
    {
        $book = Book::factory()->create(['available_copies' => 0]);
        $loan = Loan::factory()->create([
            'book_id' => $book->id,
            'borrowed_at' => now()->subDays(5),
            'returned_at' => now(),
        ]);

        $this->patch(route('loans.return', $loan))
            ->assertRedirect(route('loans.index'))
            ->assertSessionHas('error', 'Grāmata jau atgriezta!');

        $this->assertEquals(0, $book->fresh()->available_copies);
    }

    public function test_overdue_page_displays_overdue_loans(): void
    {
        $book = Book::factory()->create(['available_copies' => 1]);
        $reader = Reader::factory()->create();
        Loan::factory()->create([
            'book_id' => $book->id,
            'reader_id' => $reader->id,
            'borrowed_at' => now()->subDays(30),
            'returned_at' => null,
        ]);

        $this->get(route('loans.overdue'))
            ->assertOk()
            ->assertSee('Kavētie aizņēmumi');
    }
}
