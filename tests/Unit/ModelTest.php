<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Comment;
use App\Models\Loan;
use App\Models\Reader;
use Tests\RefreshMongoDatabase;
use Tests\TestCase;

class ModelTest extends TestCase
{
    use RefreshMongoDatabase;

    // ─── Book scopes ────────────────────────────────

    public function test_book_scope_available_filters_unavailable(): void
    {
        Book::factory()->create(['available_copies' => 0]);
        Book::factory()->create(['available_copies' => 3]);

        $available = Book::available()->get();

        $this->assertCount(1, $available);
        $this->assertEquals(3, $available->first()->available_copies);
    }

    public function test_book_scope_search_filters_by_title(): void
    {
        Book::factory()->create(['title' => 'Ātrais Zaķis']);
        Book::factory()->create(['title' => 'Lēnais Bruņurupucis']);

        $results = Book::search('Zaķis')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Ātrais Zaķis', $results->first()->title);
    }

    public function test_book_scope_search_returns_all_when_null(): void
    {
        Book::factory()->count(3)->create();

        $results = Book::search(null)->get();

        $this->assertCount(3, $results);
    }

    public function test_book_scope_with_comments_eager_loads(): void
    {
        $book = Book::factory()->create();
        Comment::create(['body' => 'A', 'commentable_id' => $book->id, 'commentable_type' => Book::class]);
        Comment::create(['body' => 'B', 'commentable_id' => $book->id, 'commentable_type' => Book::class]);

        $result = Book::withComments()->find($book->id);

        $this->assertTrue($result->relationLoaded('comments'));
        $this->assertCount(2, $result->comments);
    }

    // ─── Loan scopes ────────────────────────────────

    public function test_loan_scope_overdue(): void
    {
        Loan::factory()->create([
            'borrowed_at' => now()->subDays(30),
            'returned_at' => null,
        ]);
        Loan::factory()->create([
            'borrowed_at' => now()->subDays(5),
            'returned_at' => null,
        ]);
        Loan::factory()->create([
            'borrowed_at' => now()->subDays(30),
            'returned_at' => now(),
        ]);

        $overdue = Loan::overdue()->get();

        $this->assertCount(1, $overdue);
    }

    public function test_loan_scope_overdue_boundary(): void
    {
        Loan::factory()->create([
            'borrowed_at' => now()->subDays(14)->subMinute(),
            'returned_at' => null,
        ]);
        Loan::factory()->create([
            'borrowed_at' => now()->subDays(13),
            'returned_at' => null,
        ]);

        $this->assertCount(1, Loan::overdue()->get());
    }

    // ─── Soft Delete ────────────────────────────────

    public function test_book_soft_delete_sets_deleted_at(): void
    {
        $book = Book::factory()->create();
        $book->delete();

        $this->assertNotNull($book->fresh()->deleted_at);
    }

    public function test_book_soft_delete_excludes_from_default_queries(): void
    {
        $book = Book::factory()->create();
        $book->delete();

        $found = Book::find($book->id);
        $this->assertNull($found);
    }

    public function test_book_with_trashed_includes_deleted(): void
    {
        $book = Book::factory()->create();
        $book->delete();

        $found = Book::withTrashed()->find($book->id);
        $this->assertNotNull($found);
    }

    public function test_book_only_trashed_returns_only_deleted(): void
    {
        Book::factory()->create();
        $deleted = Book::factory()->create();
        $deleted->delete();

        $trashed = Book::onlyTrashed()->get();

        $this->assertCount(1, $trashed);
        $this->assertEquals($deleted->id, $trashed->first()->id);
    }

    // ─── Relationships ──────────────────────────────

    public function test_book_has_many_loans(): void
    {
        $book = Book::factory()->create();
        Loan::factory()->count(3)->create(['book_id' => $book->id]);

        $this->assertCount(3, $book->loans);
    }

    public function test_reader_has_many_loans(): void
    {
        $reader = Reader::factory()->create();
        Loan::factory()->count(2)->create(['reader_id' => $reader->id]);

        $this->assertCount(2, $reader->loans);
    }

    public function test_loan_belongs_to_book(): void
    {
        $book = Book::factory()->create();
        $loan = Loan::factory()->create(['book_id' => $book->id]);

        $this->assertInstanceOf(Book::class, $loan->book);
        $this->assertEquals($book->id, $loan->book->id);
    }

    public function test_loan_belongs_to_reader(): void
    {
        $reader = Reader::factory()->create();
        $loan = Loan::factory()->create(['reader_id' => $reader->id]);

        $this->assertInstanceOf(Reader::class, $loan->reader);
        $this->assertEquals($reader->id, $loan->reader->id);
    }

    // ─── Copy ───────────────────────────────────────

    public function test_copy_has_copied_from_relationship(): void
    {
        $original = Book::factory()->create();
        $copy = Book::factory()->create(['copied_from_id' => $original->id]);

        $this->assertInstanceOf(Book::class, $copy->copiedFrom);
        $this->assertEquals($original->id, $copy->copiedFrom->id);
    }

    public function test_copy_copied_from_is_null_for_original(): void
    {
        $book = Book::factory()->create();

        $this->assertNull($book->copiedFrom);
    }

    // ─── Factory ────────────────────────────────────

    public function test_book_factory_creates_valid_book(): void
    {
        $book = Book::factory()->create();

        $this->assertNotNull($book->title);
        $this->assertNotNull($book->isbn);
        $this->assertNotNull($book->available_copies);
    }

    public function test_reader_factory_creates_valid_reader(): void
    {
        $reader = Reader::factory()->create();

        $this->assertNotNull($reader->name);
        $this->assertNotNull($reader->email);
    }

    // ─── Empty relations ────────────────────────────

    public function test_book_has_no_loans_initially(): void
    {
        $book = Book::factory()->create();

        $this->assertCount(0, $book->loans);
    }

    public function test_reader_has_no_loans_initially(): void
    {
        $reader = Reader::factory()->create();

        $this->assertCount(0, $reader->loans);
    }

    public function test_book_has_no_comments_initially(): void
    {
        $book = Book::factory()->create();

        $this->assertCount(0, $book->comments);
    }

    // ─── Casts ──────────────────────────────────────

    public function test_loan_returned_at_is_carbon(): void
    {
        $loan = Loan::factory()->returned()->create();

        $this->assertInstanceOf(\Carbon\Carbon::class, $loan->returned_at);
    }

    public function test_loan_borrowed_at_is_carbon(): void
    {
        $loan = Loan::factory()->create();

        $this->assertInstanceOf(\Carbon\Carbon::class, $loan->borrowed_at);
    }

    public function test_book_trashed_status(): void
    {
        $book = Book::factory()->create();

        $this->assertFalse($book->trashed());

        $book->delete();
        $this->assertTrue($book->fresh()->trashed());

        $book->restore();
        $this->assertFalse($book->fresh()->trashed());
    }

    // ─── Idempotency ────────────────────────────────

    public function test_deleting_book_does_not_delete_others(): void
    {
        $keep = Book::factory()->create();
        $delete = Book::factory()->create();

        $delete->delete();

        $this->assertNull(Book::find($delete->id));
        $this->assertNotNull(Book::find($keep->id));
    }

    public function test_copy_does_not_affect_original_loans(): void
    {
        $book = Book::factory()->create();
        Loan::factory()->count(2)->create(['book_id' => $book->id]);

        Book::factory()->create(['copied_from_id' => $book->id]);

        $this->assertCount(2, $book->loans);
    }

    public function test_destroying_twice_is_idempotent(): void
    {
        $book = Book::factory()->create();
        $book->delete();
        $book->delete();

        $this->assertSoftDeleted($book);
    }

    // ─── DB constraints ─────────────────────────────

    public function test_database_unique_isbn_constraint(): void
    {
        Book::factory()->create(['isbn' => '9780000000001']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Book::factory()->create(['isbn' => '9780000000001']);
    }

    public function test_database_unique_email_constraint(): void
    {
        Reader::factory()->create(['email' => 'dup@example.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Reader::factory()->create(['email' => 'dup@example.com']);
    }

    public function test_database_foreign_key_constraint_on_loans_book_id(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        Loan::factory()->create(['book_id' => 999999]);
    }

    public function test_database_foreign_key_constraint_on_loans_reader_id(): void
    {
        $book = Book::factory()->create();

        $this->expectException(\Illuminate\Database\QueryException::class);
        Loan::factory()->create(['book_id' => $book->id, 'reader_id' => 999999]);
    }

    // ─── Soft delete relationships ──────────────────

    public function test_soft_deleted_book_still_has_loans(): void
    {
        $book = Book::factory()->create();
        Loan::factory()->create(['book_id' => $book->id]);
        $book->delete();

        $this->assertTrue($book->fresh()->trashed());
        $this->assertCount(1, $book->loans);
    }
}
