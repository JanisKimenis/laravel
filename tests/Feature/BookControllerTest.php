<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Journal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_displays_books(): void
    {
        Book::factory()->create(['title' => 'Testa Grāmata']);

        $this->get(route('books.index'))
            ->assertOk()
            ->assertSee('Testa Grāmata');
    }

    public function test_store_creates_book(): void
    {
        $data = [
            'title' => 'Jauna Grāmata',
            'isbn' => '978-1-2345-6789-7',
            'available_copies' => 3,
        ];

        $this->post(route('books.store'), $data)
            ->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('books', ['isbn' => '978-1-2345-6789-7']);
    }

    public function test_update_modifies_book(): void
    {
        $book = Book::factory()->create(['title' => 'Vecs Nosaukums']);

        $this->put(route('books.update', $book), [
            'title' => 'Jauns Nosaukums',
            'isbn' => $book->isbn,
            'available_copies' => 5,
        ])->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('books', ['title' => 'Jauns Nosaukums']);
    }

    public function test_destroy_deletes_book(): void
    {
        $book = Book::factory()->create();

        $this->delete(route('books.destroy', $book))
            ->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    public function test_journal_page_displays_entries(): void
    {
        $book = Book::factory()->create(['available_copies' => 5]);

        $book->decrement('available_copies');

        $this->get(route('books.journal'))
            ->assertOk()
            ->assertSee('Žurnāls')
            ->assertSee($book->title);
    }

    public function test_book_update_creates_journal_entry(): void
    {
        $book = Book::factory()->create(['available_copies' => 5]);

        $book->decrement('available_copies');

        $this->assertDatabaseHas('journals', [
            'book_id' => $book->id,
            'old_copies' => 5,
            'new_copies' => 4,
        ]);
    }
}
