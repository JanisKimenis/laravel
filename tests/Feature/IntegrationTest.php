<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Comment;
use App\Models\Loan;
use App\Models\Reader;
use Tests\RefreshMongoDatabase;
use Tests\TestCase;

class IntegrationTest extends TestCase
{
    use RefreshMongoDatabase;

    public function test_full_borrow_return_workflow(): void
    {
        $book = Book::factory()->create(['available_copies' => 3]);
        $reader = Reader::factory()->create();

        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('3');

        $this->post(route('loans.store'), [
            'book_id' => $book->id,
            'reader_id' => $reader->id,
        ])->assertRedirect(route('loans.index'));

        $this->assertEquals(2, $book->fresh()->available_copies);

        $this->assertDatabaseHas('journals', [
            'book_id' => $book->id,
            'old_copies' => 3,
            'new_copies' => 2,
        ]);

        $loan = Loan::where('book_id', $book->id)->first();

        $this->patch(route('loans.return', $loan))
            ->assertRedirect(route('loans.index'));

        $this->assertEquals(3, $book->fresh()->available_copies);
    }

    public function test_create_comment_on_borrowed_book(): void
    {
        $book = Book::factory()->create();
        $reader = Reader::factory()->create();

        Loan::factory()->create([
            'book_id' => $book->id,
            'reader_id' => $reader->id,
        ]);

        Comment::create([
            'body' => 'Grāmata tika aizņemta, viss kārtībā.',
            'commentable_id' => $book->id,
            'commentable_type' => Book::class,
        ]);

        $this->assertCount(1, $book->comments);
        $this->assertDatabaseHas('comments', [
            'body' => 'Grāmata tika aizņemta, viss kārtībā.',
        ]);
    }

    public function test_soft_delete_and_copy(): void
    {
        $book = Book::factory()->create(['title' => 'Dzēšamā Grāmata']);
        $book->delete();

        $this->post(route('books.copy', $book))
            ->assertNotFound();

        $restored = Book::withTrashed()->find($book->id);
        $restored->restore();

        $this->post(route('books.copy', $restored))
            ->assertRedirect();

        $this->assertDatabaseHas('books', [
            'title' => 'Copy of Dzēšamā Grāmata',
            'copied_from_id' => $restored->id,
        ]);
    }

    public function test_overdue_loan_fine_calculation(): void
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
            ->assertSee($expectedFine . ' EUR')
            ->assertSee(round($days) . ' d.');
    }

    public function test_search_filters_index_after_borrowing(): void
    {
        $book = Book::factory()->create(['title' => 'Meklējamā Grāmata']);
        $reader = Reader::factory()->create();

        Loan::factory()->create([
            'book_id' => $book->id,
            'reader_id' => $reader->id,
        ]);

        $this->get(route('books.index', ['q' => 'Meklējamā']))
            ->assertOk()
            ->assertSee('Meklējamā Grāmata')
            ->assertDontSee('Nav');
    }

    public function test_journal_tracks_multiple_changes(): void
    {
        $book = Book::factory()->create(['available_copies' => 5]);

        $book->decrement('available_copies');
        $book->decrement('available_copies');
        $book->decrement('available_copies');

        $entries = \App\Models\Journal::where('book_id', $book->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(3, $entries);
        $this->assertEquals(5, $entries[0]->old_copies);
        $this->assertEquals(4, $entries[0]->new_copies);
        $this->assertEquals(4, $entries[1]->old_copies);
        $this->assertEquals(3, $entries[1]->new_copies);
        $this->assertEquals(3, $entries[2]->old_copies);
        $this->assertEquals(2, $entries[2]->new_copies);
    }

    public function test_copy_preserves_relationship_chain(): void
    {
        $original = Book::factory()->create(['title' => 'Sākotnējā']);
        $copy1 = Book::factory()->create([
            'title' => 'Copy of Sākotnējā',
            'copied_from_id' => $original->id,
        ]);
        $copy2 = Book::factory()->create([
            'title' => 'Copy of Copy of Sākotnējā',
            'copied_from_id' => $copy1->id,
        ]);

        $this->assertEquals($original->id, $copy1->copiedFrom->id);
        $this->assertEquals($copy1->id, $copy2->copiedFrom->id);
        $this->assertNull($original->copiedFrom);
    }

    public function test_borrow_all_copies_then_return_one(): void
    {
        $book = Book::factory()->create(['available_copies' => 2]);
        $r1 = Reader::factory()->create();
        $r2 = Reader::factory()->create();

        $this->post(route('loans.store'), ['book_id' => $book->id, 'reader_id' => $r1->id]);
        $this->post(route('loans.store'), ['book_id' => $book->id, 'reader_id' => $r2->id]);
        $this->assertEquals(0, $book->fresh()->available_copies);

        $this->post(route('loans.store'), ['book_id' => $book->id, 'reader_id' => $r1->id])
            ->assertRedirect(route('loans.create'))->assertSessionHas('error');

        $firstLoan = Loan::where('book_id', $book->id)->where('reader_id', $r1->id)->first();
        $this->patch(route('loans.return', $firstLoan));
        $this->assertEquals(1, $book->fresh()->available_copies);
    }

    public function test_create_rate_then_verify_overdue_fine(): void
    {
        $this->post(route('fines.update'), ['rate' => 2.50]);

        $book = Book::factory()->create();
        $reader = Reader::factory()->create();
        $loan = Loan::factory()->create([
            'book_id' => $book->id,
            'reader_id' => $reader->id,
            'borrowed_at' => now()->subDays(20),
            'returned_at' => null,
        ]);

        $days = now()->diffInDays($loan->borrowed_at);
        $expected = number_format(round($days * 2.50, 2), 2);

        $this->get(route('loans.overdue'))
            ->assertOk()
            ->assertSee($expected . ' EUR');
    }

    public function test_delete_reader_then_verify_loans_gone(): void
    {
        $reader = Reader::factory()->create();
        Loan::factory()->count(2)->create(['reader_id' => $reader->id]);

        $reader->delete();
        $this->assertDatabaseMissing('readers', ['id' => $reader->id]);
        $this->assertDatabaseMissing('loans', ['reader_id' => $reader->id]);
    }

    public function test_soft_delete_book_preserves_loans(): void
    {
        $book = Book::factory()->create();
        Loan::factory()->count(2)->create(['book_id' => $book->id]);

        $book->delete();
        $this->assertSoftDeleted($book);
        $this->assertDatabaseHas('loans', ['book_id' => $book->id]);
    }
}
