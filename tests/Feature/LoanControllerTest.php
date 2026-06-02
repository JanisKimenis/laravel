<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Reader;
use Tests\RefreshMongoDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LoanControllerTest extends TestCase
{
    use RefreshMongoDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('settings')->updateOrInsert(
            ['key' => 'fine_per_day'],
            ['value' => '0.50', 'created_at' => now(), 'updated_at' => now()]
        );
    }

    public function test_index_displays_loans(): void
    {
        $book = Book::factory()->create();
        $reader = Reader::factory()->create();
        Loan::factory()->create([
            'book_id' => $book->id,
            'reader_id' => $reader->id,
        ]);

        $this->get(route('loans.index'))
            ->assertOk()
            ->assertSee('Aizņēmumi');
    }

    public function test_create_page_shows_only_available_books(): void
    {
        Book::factory()->create(['title' => 'Pieejamā', 'available_copies' => 3]);
        Book::factory()->create(['title' => 'Nepieejamā', 'available_copies' => 0]);

        $this->get(route('loans.create'))
            ->assertOk()
            ->assertSee('Pieejamā')
            ->assertDontSee('Nepieejamā');
    }

    public function test_store_fails_without_book_id(): void
    {
        $reader = Reader::factory()->create();

        $this->post(route('loans.store'), [
            'reader_id' => $reader->id,
        ])->assertSessionHasErrors('book_id');
    }

    public function test_store_fails_without_reader_id(): void
    {
        $book = Book::factory()->create(['available_copies' => 1]);

        $this->post(route('loans.store'), [
            'book_id' => $book->id,
        ])->assertSessionHasErrors('reader_id');
    }

    public function test_store_fails_with_nonexistent_book(): void
    {
        $reader = Reader::factory()->create();

        $this->post(route('loans.store'), [
            'book_id' => 999999,
            'reader_id' => $reader->id,
        ])->assertSessionHasErrors('book_id');
    }

    public function test_store_fails_with_nonexistent_reader(): void
    {
        $book = Book::factory()->create(['available_copies' => 1]);

        $this->post(route('loans.store'), [
            'book_id' => $book->id,
            'reader_id' => 999999,
        ])->assertSessionHasErrors('reader_id');
    }

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

    public function test_return_book_updates_timestamp(): void
    {
        $book = Book::factory()->create(['available_copies' => 0]);
        $loan = Loan::factory()->create([
            'book_id' => $book->id,
            'borrowed_at' => now()->subDays(3),
            'returned_at' => null,
        ]);

        $this->patch(route('loans.return', $loan));

        $returned = $loan->fresh()->returned_at;
        $this->assertNotNull($returned);
        $this->assertEqualsWithDelta(now()->timestamp, $returned->timestamp, 2);
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

    public function test_overdue_page_shows_empty_when_no_overdue(): void
    {
        $this->get(route('loans.overdue'))
            ->assertOk()
            ->assertSee('Nav kavētu aizņēmumu');
    }

    public function test_overdue_page_shows_fine_amount(): void
    {
        $book = Book::factory()->create();
        $reader = Reader::factory()->create();
        $loan = Loan::factory()->create([
            'book_id' => $book->id,
            'reader_id' => $reader->id,
            'borrowed_at' => now()->subDays(20),
            'returned_at' => null,
        ]);

        $days = now()->diffInDays($loan->borrowed_at);
        $expectedFine = number_format(round($days * 0.50, 2), 2);

        $this->get(route('loans.overdue'))
            ->assertOk()
            ->assertSee($expectedFine . ' EUR');
    }

    public function test_return_book_returns_404_for_nonexistent_loan(): void
    {
        $this->patch(route('loans.return', 999999))
            ->assertNotFound();
    }

    public function test_borrow_same_book_after_return(): void
    {
        $book = Book::factory()->create(['available_copies' => 1]);
        $reader = Reader::factory()->create();

        $this->post(route('loans.store'), [
            'book_id' => $book->id,
            'reader_id' => $reader->id,
        ]);
        $this->assertEquals(0, $book->fresh()->available_copies);

        $loan = Loan::first();
        $this->patch(route('loans.return', $loan));
        $this->assertEquals(1, $book->fresh()->available_copies);

        $this->post(route('loans.store'), [
            'book_id' => $book->id,
            'reader_id' => $reader->id,
        ])->assertRedirect(route('loans.index'));

        $this->assertEquals(2, Loan::count());
        $this->assertEquals(0, $book->fresh()->available_copies);
    }

    public function test_overdue_shows_correct_days(): void
    {
        $book = Book::factory()->create();
        $reader = Reader::factory()->create();
        $loan = Loan::factory()->create([
            'book_id' => $book->id,
            'reader_id' => $reader->id,
            'borrowed_at' => now()->subDays(18),
            'returned_at' => null,
        ]);

        $this->get(route('loans.overdue'))
            ->assertOk()
            ->assertSee('18 d.');
    }

    public function test_create_page_lists_all_readers(): void
    {
        Book::factory()->create(['available_copies' => 1]);
        Reader::factory()->create(['name' => 'Lasītājs A']);
        Reader::factory()->create(['name' => 'Lasītājs B']);

        $this->get(route('loans.create'))
            ->assertOk()
            ->assertSee('Lasītājs A')
            ->assertSee('Lasītājs B');
    }

    public function test_store_redirects_with_success_message(): void
    {
        $book = Book::factory()->create(['available_copies' => 1]);
        $reader = Reader::factory()->create();

        $this->post(route('loans.store'), [
            'book_id' => $book->id,
            'reader_id' => $reader->id,
        ])->assertSessionHas('success');
    }

    public function test_return_redirects_with_success_message(): void
    {
        $book = Book::factory()->create(['available_copies' => 0]);
        $loan = Loan::factory()->create([
            'book_id' => $book->id,
            'borrowed_at' => now(),
            'returned_at' => null,
        ]);

        $this->patch(route('loans.return', $loan))
            ->assertSessionHas('success');
    }

    public function test_index_displays_loan_details(): void
    {
        $book = Book::factory()->create(['title' => 'Aizņemtā Grāmata']);
        $reader = Reader::factory()->create(['name' => 'Aizņēmējs']);
        Loan::factory()->create([
            'book_id' => $book->id,
            'reader_id' => $reader->id,
            'borrowed_at' => now()->subDays(3),
            'returned_at' => null,
        ]);

        $this->get(route('loans.index'))
            ->assertOk()
            ->assertSee('Aizņemtā Grāmata')
            ->assertSee('Aizņēmējs');
    }

    public function test_overdue_boundary_14_days_exact(): void
    {
        Book::factory()->create();
        Reader::factory()->create();
        Loan::factory()->create([
            'borrowed_at' => now()->subDays(14),
            'returned_at' => null,
        ]);

        $this->get(route('loans.overdue'))
            ->assertOk();
    }

    public function test_cannot_borrow_deleted_book(): void
    {
        $reader = Reader::factory()->create();
        $book = Book::factory()->create(['available_copies' => 1]);
        $book->delete();

        $this->post(route('loans.store'), [
            'book_id' => $book->id,
            'reader_id' => $reader->id,
        ])->assertNotFound();
    }

    public function test_store_creates_journal_entry(): void
    {
        $book = Book::factory()->create(['available_copies' => 5]);
        $reader = Reader::factory()->create();

        $this->post(route('loans.store'), [
            'book_id' => $book->id,
            'reader_id' => $reader->id,
        ]);

        $this->assertDatabaseHas('journals', [
            'book_id' => $book->id,
            'old_copies' => 5,
            'new_copies' => 4,
        ]);
    }

    public function test_overdue_page_shows_rate_from_settings(): void
    {
        $this->get(route('loans.overdue'))
            ->assertOk()
            ->assertSee('0.50 EUR/dienā');
    }

    public function test_store_does_not_create_loan_for_unavailable_book_via_validation(): void
    {
        $book = Book::factory()->create(['available_copies' => 1]);
        $reader = Reader::factory()->create();

        $otherBook = Book::factory()->create(['available_copies' => 0]);

        $this->post(route('loans.store'), [
            'book_id' => $otherBook->id,
            'reader_id' => $reader->id,
        ])->assertRedirect(route('loans.create'))->assertSessionHas('error');

        $this->assertEquals(1, $book->fresh()->available_copies);
    }

    public function test_index_shows_returned_date_for_returned_loans(): void
    {
        $book = Book::factory()->create();
        $reader = Reader::factory()->create();
        Loan::factory()->returned()->create([
            'book_id' => $book->id,
            'reader_id' => $reader->id,
        ]);

        $this->get(route('loans.index'))
            ->assertOk()
            ->assertSee($book->title);
    }

    public function test_returning_loan_updates_journal(): void
    {
        $book = Book::factory()->create(['available_copies' => 0]);
        $loan = Loan::factory()->create([
            'book_id' => $book->id,
            'borrowed_at' => now(),
            'returned_at' => null,
        ]);

        $this->patch(route('loans.return', $loan));

        $this->assertDatabaseHas('journals', [
            'book_id' => $book->id,
            'old_copies' => 0,
            'new_copies' => 1,
        ]);
    }

    public function test_create_page_does_not_show_books_with_zero_copies(): void
    {
        Book::factory()->create(['title' => 'Nepieejamā', 'available_copies' => 0]);
        Book::factory()->create(['title' => 'Pieejamā', 'available_copies' => 2]);

        $this->get(route('loans.create'))
            ->assertOk()
            ->assertSee('Pieejamā')
            ->assertDontSee('Nepieejamā');
    }
}
