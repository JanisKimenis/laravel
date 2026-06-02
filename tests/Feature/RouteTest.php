<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Reader;
use Tests\RefreshMongoDatabase;
use Tests\TestCase;

class RouteTest extends TestCase
{
    use RefreshMongoDatabase;

    public function test_root_redirects_to_books(): void
    {
        $this->get('/')->assertRedirect('/books');
    }

    public function test_books_index_route(): void
    {
        $this->get('/books')->assertOk();
    }

    public function test_books_create_route(): void
    {
        $this->get('/books/create')->assertOk();
    }

    public function test_books_store_route(): void
    {
        $this->post('/books', [
            'title' => 'Test',
            'isbn' => '9780000000099',
            'available_copies' => 1,
        ])->assertRedirect('/books');
    }

    public function test_books_show_route(): void
    {
        $book = Book::factory()->create();
        $this->get("/books/{$book->id}")->assertOk();
    }

    public function test_books_edit_route(): void
    {
        $book = Book::factory()->create();
        $this->get("/books/{$book->id}/edit")->assertOk();
    }

    public function test_books_update_route(): void
    {
        $book = Book::factory()->create();
        $this->put("/books/{$book->id}", [
            'title' => $book->title,
            'isbn' => $book->isbn,
            'available_copies' => $book->available_copies,
        ])->assertRedirect('/books');
    }

    public function test_books_destroy_route(): void
    {
        $book = Book::factory()->create();
        $this->delete("/books/{$book->id}")->assertRedirect('/books');
    }

    public function test_books_journal_route(): void
    {
        $this->get('/books/journal')->assertOk();
    }

    public function test_books_copy_route(): void
    {
        $book = Book::factory()->create();
        $this->post("/books/{$book->id}/copy")->assertRedirect();
    }

    public function test_readers_index_route(): void
    {
        $this->get('/readers')->assertOk();
    }

    public function test_readers_create_route(): void
    {
        $this->get('/readers/create')->assertOk();
    }

    public function test_readers_store_route(): void
    {
        $this->post('/readers', [
            'name' => 'Test',
            'email' => 'route@test.com',
        ])->assertRedirect('/readers');
    }

    public function test_readers_edit_route(): void
    {
        $reader = Reader::factory()->create();
        $this->get("/readers/{$reader->id}/edit")->assertOk();
    }

    public function test_readers_update_route(): void
    {
        $reader = Reader::factory()->create();
        $this->put("/readers/{$reader->id}", [
            'name' => $reader->name,
            'email' => $reader->email,
        ])->assertRedirect('/readers');
    }

    public function test_readers_destroy_route(): void
    {
        $reader = Reader::factory()->create();
        $this->delete("/readers/{$reader->id}")->assertRedirect('/readers');
    }

    public function test_loans_index_route(): void
    {
        $this->get('/loans')->assertOk();
    }

    public function test_loans_create_route(): void
    {
        $this->get('/loans/create')->assertOk();
    }

    public function test_loans_overdue_route(): void
    {
        $this->get('/loans/overdue')->assertOk();
    }

    public function test_fines_index_route(): void
    {
        $this->get('/fines')->assertOk();
    }

    public function test_benchmark_index_route(): void
    {
        $this->get('/benchmark')->assertOk();
    }

    public function test_books_show_returns_404_for_missing(): void
    {
        $this->get('/books/999999')->assertNotFound();
    }

    public function test_post_route_without_csrf_works_in_tests(): void
    {
        $this->withoutMiddleware()
            ->post('/books', [
                'title' => 'CSRF Test',
                'isbn' => '9780000000098',
                'available_copies' => 1,
            ])->assertRedirect('/books');
    }
}
