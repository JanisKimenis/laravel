<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Comment;
use App\Models\Reader;
use Tests\RefreshMongoDatabase;
use Tests\TestCase;

class CommentControllerTest extends TestCase
{
    use RefreshMongoDatabase;

    public function test_can_create_comment_on_book(): void
    {
        $book = Book::factory()->create();

        $comment = Comment::create([
            'body' => 'Lieliska grāmata!',
            'commentable_id' => $book->id,
            'commentable_type' => Book::class,
        ]);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'body' => 'Lieliska grāmata!',
            'commentable_id' => $book->id,
            'commentable_type' => Book::class,
        ]);
    }

    public function test_can_create_comment_on_reader(): void
    {
        $reader = Reader::factory()->create();

        $comment = Comment::create([
            'body' => 'Aktīvs lasītājs',
            'commentable_id' => $reader->id,
            'commentable_type' => Reader::class,
        ]);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'body' => 'Aktīvs lasītājs',
            'commentable_id' => $reader->id,
            'commentable_type' => Reader::class,
        ]);
    }

    public function test_book_has_many_comments(): void
    {
        $book = Book::factory()->create();

        Comment::create(['body' => 'Pirmais', 'commentable_id' => $book->id, 'commentable_type' => Book::class]);
        Comment::create(['body' => 'Otrais', 'commentable_id' => $book->id, 'commentable_type' => Book::class]);

        $this->assertCount(2, $book->comments);
    }

    public function test_reader_has_many_comments(): void
    {
        $reader = Reader::factory()->create();

        Comment::create(['body' => 'Piezīme', 'commentable_id' => $reader->id, 'commentable_type' => Reader::class]);

        $this->assertCount(1, $reader->comments);
    }

    public function test_comment_belongs_to_book_via_morph(): void
    {
        $book = Book::factory()->create();
        $comment = Comment::create([
            'body' => 'Testa komentārs',
            'commentable_id' => $book->id,
            'commentable_type' => Book::class,
        ]);

        $this->assertInstanceOf(Book::class, $comment->commentable);
        $this->assertEquals($book->id, $comment->commentable->id);
    }

    public function test_comment_belongs_to_reader_via_morph(): void
    {
        $reader = Reader::factory()->create();
        $comment = Comment::create([
            'body' => 'Lasītāja komentārs',
            'commentable_id' => $reader->id,
            'commentable_type' => Reader::class,
        ]);

        $this->assertInstanceOf(Reader::class, $comment->commentable);
        $this->assertEquals($reader->id, $comment->commentable->id);
    }

    public function test_comment_with_null_commentable_id_is_filtered(): void
    {
        $comment = Comment::create([
            'body' => 'Nederīgs komentārs',
            'commentable_id' => null,
            'commentable_type' => Book::class,
        ]);

        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
        $this->assertNull(Comment::find($comment->id));
    }

    public function test_scope_with_comments_eager_loads(): void
    {
        $book = Book::factory()->create();
        Comment::create(['body' => 'Eager', 'commentable_id' => $book->id, 'commentable_type' => Book::class]);

        $loaded = Book::withComments()->find($book->id);
        $this->assertTrue($loaded->relationLoaded('comments'));
        $this->assertCount(1, $loaded->comments);
    }

    public function test_comment_timestamps_are_set(): void
    {
        $book = Book::factory()->create();
        $comment = Comment::create([
            'body' => 'Laikspieds',
            'commentable_id' => $book->id,
            'commentable_type' => Book::class,
        ]);

        $this->assertNotNull($comment->created_at);
        $this->assertNotNull($comment->updated_at);
    }

    public function test_delete_comment(): void
    {
        $book = Book::factory()->create();
        $comment = Comment::create([
            'body' => 'Dzēšams',
            'commentable_id' => $book->id,
            'commentable_type' => Book::class,
        ]);

        $comment->delete();

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_multiple_comments_on_same_book(): void
    {
        $book = Book::factory()->create();

        Comment::create(['body' => 'Pirmais', 'commentable_id' => $book->id, 'commentable_type' => Book::class]);
        Comment::create(['body' => 'Otrais', 'commentable_id' => $book->id, 'commentable_type' => Book::class]);
        Comment::create(['body' => 'Trešais', 'commentable_id' => $book->id, 'commentable_type' => Book::class]);

        $this->assertCount(3, $book->comments()->withoutGlobalScopes()->get());
    }

    public function test_mixed_comments_on_book_and_reader(): void
    {
        $book = Book::factory()->create();
        $reader = Reader::factory()->create();

        Comment::create(['body' => 'Par grāmatu', 'commentable_id' => $book->id, 'commentable_type' => Book::class]);
        Comment::create(['body' => 'Par lasītāju', 'commentable_id' => $reader->id, 'commentable_type' => Reader::class]);

        $this->assertCount(1, $book->comments);
        $this->assertCount(1, $reader->comments);
    }

    public function test_comment_with_invalid_type_not_found(): void
    {
        $comment = Comment::create([
            'body' => 'Nederīgs tips',
            'commentable_id' => 1,
            'commentable_type' => 'NonExistentModel',
        ]);

        $found = Comment::withoutGlobalScopes()->find($comment->id);
        $this->assertNotNull($found);
        $this->assertNull($found->commentable);
    }

    public function test_empty_comments_on_new_book(): void
    {
        $book = Book::factory()->create();

        $this->assertCount(0, $book->comments);
    }

    public function test_comment_body_unicode(): void
    {
        $book = Book::factory()->create();

        Comment::create([
            'body' => 'Komentārs ar unikodu: 日本語 Русский 😊',
            'commentable_id' => $book->id,
            'commentable_type' => Book::class,
        ]);

        $this->assertDatabaseHas('comments', [
            'body' => 'Komentārs ar unikodu: 日本語 Русский 😊',
        ]);
    }

    public function test_multiple_comments_have_different_ids(): void
    {
        $book = Book::factory()->create();

        $c1 = Comment::create(['body' => 'A', 'commentable_id' => $book->id, 'commentable_type' => Book::class]);
        $c2 = Comment::create(['body' => 'B', 'commentable_id' => $book->id, 'commentable_type' => Book::class]);

        $this->assertNotEquals($c1->id, $c2->id);
    }

    public function test_comment_ordering_by_id(): void
    {
        $book = Book::factory()->create();

        Comment::create(['body' => 'Pirmais', 'commentable_id' => $book->id, 'commentable_type' => Book::class]);
        Comment::create(['body' => 'Otrais', 'commentable_id' => $book->id, 'commentable_type' => Book::class]);

        $comments = $book->comments()->withoutGlobalScopes()->orderBy('id')->get();
        $this->assertEquals('Pirmais', $comments[0]->body);
        $this->assertEquals('Otrais', $comments[1]->body);
    }

    public function test_global_scope_filters_null_commentable_id(): void
    {
        Comment::create(['body' => 'Redzams', 'commentable_id' => null, 'commentable_type' => Book::class]);

        $this->assertNull(Comment::where('body', 'Redzams')->first());
    }
}
