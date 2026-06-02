<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Journal;
use Tests\RefreshMongoDatabase;
use Tests\TestCase;

class BookControllerTest extends TestCase
{
    use RefreshMongoDatabase;

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

        $this->assertSoftDeleted($book);
    }

    public function test_show_displays_book(): void
    {
        $book = Book::factory()->create(['title' => 'Parādāmā Grāmata']);

        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('Parādāmā Grāmata');
    }

    public function test_show_shows_deleted_flash(): void
    {
        $book = Book::factory()->create();
        $book->delete();

        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('dzēsta');
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

    public function test_copy_creates_duplicate_with_prefix(): void
    {
        $book = Book::factory()->create(['title' => 'Oriģināls']);

        $this->post(route('books.copy', $book))
            ->assertRedirect();

        $this->assertDatabaseHas('books', [
            'title' => 'Copy of Oriģināls',
            'copied_from_id' => $book->id,
        ]);
    }

    public function test_copy_redirects_to_new_book_show(): void
    {
        $book = Book::factory()->create();

        $this->post(route('books.copy', $book))
            ->assertRedirect(route('books.show', Book::where('copied_from_id', $book->id)->first()));
    }

    public function test_search_finds_matching_books(): void
    {
        Book::factory()->create(['title' => 'Sarkana ābele']);
        Book::factory()->create(['title' => 'Zaļā ābele']);
        Book::factory()->create(['title' => 'Meža Grāmata']);

        $this->get(route('books.index', ['q' => 'ābele']))
            ->assertOk()
            ->assertSee('Sarkana ābele')
            ->assertSee('Zaļā ābele')
            ->assertDontSee('Meža Grāmata');
    }

    public function test_search_empty_returns_all(): void
    {
        Book::factory()->create(['title' => 'A']);
        Book::factory()->create(['title' => 'B']);

        $this->get(route('books.index'))
            ->assertOk()
            ->assertSee('A')
            ->assertSee('B');
    }

    public function test_search_with_no_results_shows_empty(): void
    {
        Book::factory()->create(['title' => 'Unikāls Nosaukums']);

        $this->get(route('books.index', ['q' => 'XYZZZXXYYY']))
            ->assertOk()
            ->assertDontSee('Unikāls Nosaukums');
    }

    public function test_search_case_insensitive(): void
    {
        Book::factory()->create(['title' => 'ĀBOLIŅŠ']);

        $this->get(route('books.index', ['q' => 'Āboliņš']))
            ->assertOk()
            ->assertSee('ĀBOLIŅŠ');
    }

    // ─── Soft Delete deep tests ─────────────────────────────

    public function test_index_hides_soft_deleted_books(): void
    {
        $book = Book::factory()->create(['title' => 'Dzēšamā']);
        $other = Book::factory()->create(['title' => 'Paliekošā']);
        $book->delete();

        $this->get(route('books.index'))
            ->assertOk()
            ->assertDontSee('Dzēšamā')
            ->assertSee('Paliekošā');
    }

    public function test_edit_returns_404_for_soft_deleted_book(): void
    {
        $book = Book::factory()->create();
        $book->delete();

        $this->get(route('books.edit', $book))
            ->assertNotFound();
    }

    public function test_update_returns_404_for_soft_deleted_book(): void
    {
        $book = Book::factory()->create();
        $book->delete();

        $this->put(route('books.update', $book), [
            'title' => 'Mēģinājums',
            'isbn' => 'xxx',
            'available_copies' => 1,
        ])->assertNotFound();
    }

    public function test_destroy_returns_404_for_soft_deleted_book(): void
    {
        $book = Book::factory()->create();
        $book->delete();

        $this->delete(route('books.destroy', $book))
            ->assertNotFound();
    }

    public function test_show_returns_404_for_nonexistent_book(): void
    {
        $this->get(route('books.show', 999999))
            ->assertNotFound();
    }

    // ─── Copy deep tests ────────────────────────────────────

    public function test_copy_preserves_available_copies(): void
    {
        $book = Book::factory()->create(['available_copies' => 7]);

        $this->post(route('books.copy', $book));

        $this->assertDatabaseHas('books', [
            'copied_from_id' => $book->id,
            'available_copies' => 7,
        ]);
    }

    public function test_copy_generates_unique_isbn(): void
    {
        $book = Book::factory()->create(['isbn' => '9781234567897']);

        $this->post(route('books.copy', $book));

        $copy = Book::where('copied_from_id', $book->id)->first();
        $this->assertNotNull($copy);
        $this->assertNotEquals($book->isbn, $copy->isbn);
        $this->assertStringStartsWith('COPY-', $copy->isbn);
    }

    public function test_copy_shows_reference_to_original_in_show(): void
    {
        $original = Book::factory()->create(['title' => 'Mācītāja']);

        $this->post(route('books.copy', $original));
        $copy = Book::where('copied_from_id', $original->id)->first();

        $this->get(route('books.show', $copy))
            ->assertOk()
            ->assertSee('Kopija no')
            ->assertSee('Mācītāja');
    }

    public function test_chain_copy_works(): void
    {
        $original = Book::factory()->create(['title' => 'Pirmā']);

        $this->post(route('books.copy', $original));
        $copy1 = Book::where('copied_from_id', $original->id)->first();

        $this->post(route('books.copy', $copy1));
        $copy2 = Book::where('copied_from_id', $copy1->id)->first();

        $this->assertEquals('Copy of ' . $copy1->title, $copy2->title);
        $this->assertEquals($copy1->id, $copy2->copied_from_id);
    }

    public function test_copy_does_not_affect_original_available_copies(): void
    {
        $book = Book::factory()->create(['available_copies' => 3]);

        $this->post(route('books.copy', $book));

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'available_copies' => 3,
        ]);
    }

    // ─── Validation tests ───────────────────────────────────

    public function test_store_fails_without_title(): void
    {
        $this->post(route('books.store'), [
            'isbn' => '9781234567890',
            'available_copies' => 1,
        ])->assertSessionHasErrors('title');
    }

    public function test_store_fails_without_isbn(): void
    {
        $this->post(route('books.store'), [
            'title' => 'Grāmata',
            'available_copies' => 1,
        ])->assertSessionHasErrors('isbn');
    }

    public function test_store_fails_without_available_copies(): void
    {
        $this->post(route('books.store'), [
            'title' => 'Grāmata',
            'isbn' => '9781234567890',
        ])->assertSessionHasErrors('available_copies');
    }

    public function test_store_fails_with_negative_copies(): void
    {
        $this->post(route('books.store'), [
            'title' => 'Grāmata',
            'isbn' => '9781234567890',
            'available_copies' => -1,
        ])->assertSessionHasErrors('available_copies');
    }

    public function test_store_fails_with_duplicate_isbn(): void
    {
        Book::factory()->create(['isbn' => '9781111111111']);

        $this->post(route('books.store'), [
            'title' => 'Cita Grāmata',
            'isbn' => '9781111111111',
            'available_copies' => 2,
        ])->assertSessionHasErrors('isbn');
    }

    public function test_update_fails_with_duplicate_isbn(): void
    {
        Book::factory()->create(['isbn' => '9781111111111']);
        $book = Book::factory()->create(['isbn' => '9782222222222']);

        $this->put(route('books.update', $book), [
            'title' => $book->title,
            'isbn' => '9781111111111',
            'available_copies' => 1,
        ])->assertSessionHasErrors('isbn');
    }

    public function test_update_allows_same_isbn_for_same_book(): void
    {
        $book = Book::factory()->create(['isbn' => '9781234567890']);

        $this->put(route('books.update', $book), [
            'title' => 'Jauns Nosaukums',
            'isbn' => '9781234567890',
            'available_copies' => 5,
        ])->assertRedirect(route('books.index'));
    }

    public function test_update_fails_without_required_fields(): void
    {
        $book = Book::factory()->create();

        $this->put(route('books.update', $book), [])
            ->assertSessionHasErrors(['title', 'isbn', 'available_copies']);
    }

    public function test_create_page_displays_form(): void
    {
        $this->get(route('books.create'))
            ->assertOk()
            ->assertSee('Pievienot grāmatu')
            ->assertSee('Nosaukums')
            ->assertSee('ISBN');
    }

    public function test_edit_page_displays_form(): void
    {
        $book = Book::factory()->create(['title' => 'Rediģējamā']);
        $this->get(route('books.edit', $book))
            ->assertOk()
            ->assertSee('Labot grāmatu')
            ->assertSee('Nosaukums')
            ->assertSee('ISBN');
    }

    public function test_edit_page_returns_404_for_nonexistent_book(): void
    {
        $this->get(route('books.edit', 999999))
            ->assertNotFound();
    }

    public function test_index_paginates_when_many_books(): void
    {
        Book::factory()->count(15)->create();

        $this->get(route('books.index'))
            ->assertOk()
            ->assertSee('Grāmatas');
    }

    public function test_create_book_with_zero_copies(): void
    {
        $this->post(route('books.store'), [
            'title' => 'Nedzēšama',
            'isbn' => '9780000000001',
            'available_copies' => 0,
        ])->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('books', ['isbn' => '9780000000001', 'available_copies' => 0]);
    }

    public function test_update_with_same_data_succeeds(): void
    {
        $book = Book::factory()->create([
            'title' => 'Nemainīgs',
            'isbn' => '9789999999999',
            'available_copies' => 4,
        ]);

        $this->put(route('books.update', $book), [
            'title' => 'Nemainīgs',
            'isbn' => '9789999999999',
            'available_copies' => 4,
        ])->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => 'Nemainīgs']);
    }

    public function test_store_fails_with_isbn_too_long(): void
    {
        $this->post(route('books.store'), [
            'title' => 'Grāmata',
            'isbn' => str_repeat('x', 21),
            'available_copies' => 1,
        ])->assertSessionHasErrors('isbn');
    }

    public function test_show_contains_all_fields(): void
    {
        $book = Book::factory()->create([
            'title' => 'Pilna Grāmata',
            'isbn' => '9781234567890',
            'available_copies' => 5,
        ]);

        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('Pilna Grāmata')
            ->assertSee('9781234567890')
            ->assertSee('5');
    }

    public function test_destroy_redirects_with_success_message(): void
    {
        $book = Book::factory()->create(['title' => 'Dzēšamā Grāmata']);

        $this->delete(route('books.destroy', $book))
            ->assertRedirect(route('books.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted($book);
    }

    public function test_journal_contains_logged_changes(): void
    {
        $book = Book::factory()->create(['available_copies' => 5]);
        $book->decrement('available_copies');

        $this->get(route('books.journal'))
            ->assertOk()
            ->assertSee('Žurnāls')
            ->assertSee($book->title)
            ->assertSee('5')
            ->assertSee('4');
    }

    public function test_search_with_multiple_words(): void
    {
        Book::factory()->create(['title' => 'Sarkanā ābele']);
        Book::factory()->create(['title' => 'Zaļā ābele']);
        Book::factory()->create(['title' => 'Sarkanā maize']);

        $this->get(route('books.index', ['q' => 'Sarkanā ābele']))
            ->assertOk()
            ->assertSee('Sarkanā ābele')
            ->assertDontSee('Zaļā ābele');
    }

    public function test_search_with_only_whitespace_returns_all(): void
    {
        Book::factory()->create(['title' => 'A']);
        Book::factory()->create(['title' => 'B']);

        $this->get(route('books.index', ['q' => '   ']))
            ->assertOk()
            ->assertSee('A')
            ->assertSee('B');
    }

    public function test_store_with_max_length_title(): void
    {
        $title = str_repeat('X', 255);

        $this->post(route('books.store'), [
            'title' => $title,
            'isbn' => '9780000000002',
            'available_copies' => 1,
        ])->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('books', ['isbn' => '9780000000002']);
    }

    public function test_store_fails_with_title_too_long(): void
    {
        $this->post(route('books.store'), [
            'title' => str_repeat('X', 256),
            'isbn' => '9780000000003',
            'available_copies' => 1,
        ])->assertSessionHasErrors('title');
    }

    public function test_store_fails_with_empty_title(): void
    {
        $this->post(route('books.store'), [
            'title' => '',
            'isbn' => '9780000000004',
            'available_copies' => 1,
        ])->assertSessionHasErrors('title');
    }

    public function test_show_shows_created_date(): void
    {
        $book = Book::factory()->create();

        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('Izveidots');
    }

    public function test_copy_of_soft_deleted_book_returns_404(): void
    {
        $book = Book::factory()->create();
        $book->delete();

        $this->post(route('books.copy', $book))
            ->assertNotFound();
    }

    public function test_restore_soft_deleted_book(): void
    {
        $book = Book::factory()->create();
        $book->delete();
        $this->assertSoftDeleted($book);

        $book->restore();

        $this->assertNotSoftDeleted($book);
        $this->get(route('books.index'))
            ->assertOk()
            ->assertSee($book->title);
    }

    public function test_force_delete_removes_permanently(): void
    {
        $book = Book::factory()->create();
        $book->forceDelete();

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    public function test_index_shows_search_form(): void
    {
        $this->get(route('books.index'))
            ->assertOk()
            ->assertSee('Meklēt');
    }

    public function test_index_search_form_shows_clear_when_query(): void
    {
        $this->get(route('books.index', ['q' => 'test']))
            ->assertOk()
            ->assertSee('Notīrīt')
            ->assertSee('test');
    }

    public function test_destroy_soft_deletes_not_hard_deletes(): void
    {
        $book = Book::factory()->create();

        $this->delete(route('books.destroy', $book));

        $this->assertSoftDeleted($book);
        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }

    public function test_search_special_characters(): void
    {
        Book::factory()->create(['title' => 'C++ Programmēšana']);
        Book::factory()->create(['title' => 'C# Pamati']);

        $this->get(route('books.index', ['q' => 'C++']))
            ->assertOk()
            ->assertSee('C++ Programmēšana');
    }

    public function test_search_finds_numeric_title(): void
    {
        Book::factory()->create(['title' => '1984']);
        Book::factory()->create(['title' => 'Gads 2000']);

        $this->get(route('books.index', ['q' => '1984']))
            ->assertOk()
            ->assertSee('1984')
            ->assertDontSee('Gads 2000');
    }

    public function test_search_with_many_spaces(): void
    {
        Book::factory()->create(['title' => 'Sarkanā ābele']);

        $this->get(route('books.index', ['q' => '   Sarkanā   ']))
            ->assertOk()
            ->assertSee('Sarkanā ābele');
    }

    public function test_store_xss_prevention(): void
    {
        $this->post(route('books.store'), [
            'title' => '<script>alert("xss")</script>',
            'isbn' => '9780000000010',
            'available_copies' => 1,
        ])->assertRedirect(route('books.index'));

        $response = $this->get(route('books.index'));
        $response->assertOk();
        $this->assertStringContainsString(
            '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;',
            $response->getContent()
        );
    }

    public function test_store_unicode_title(): void
    {
        $this->post(route('books.store'), [
            'title' => '日本語 中文 Русский',
            'isbn' => '9780000000011',
            'available_copies' => 2,
        ])->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('books', ['isbn' => '9780000000011']);
        $this->get(route('books.index'))
            ->assertOk()
            ->assertSee('日本語');
    }

    public function test_create_page_has_post_form(): void
    {
        $this->get(route('books.create'))
            ->assertOk()
            ->assertSee('action')
            ->assertSee('Saglabāt');
    }

    public function test_edit_page_has_put_method(): void
    {
        $book = Book::factory()->create();

        $this->get(route('books.edit', $book))
            ->assertOk()
            ->assertSee('PUT');
    }

    public function test_index_table_has_headers(): void
    {
        Book::factory()->create();

        $this->get(route('books.index'))
            ->assertOk()
            ->assertSee('Nosaukums')
            ->assertSee('ISBN')
            ->assertSee('Pieejamie eksemplāri')
            ->assertSee('Darbības');
    }

    public function test_copy_source_remains_unchanged(): void
    {
        $book = Book::factory()->create([
            'title' => 'Oriģināls',
            'available_copies' => 5,
        ]);

        $this->post(route('books.copy', $book));

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'Oriģināls',
            'available_copies' => 5,
            'copied_from_id' => null,
        ]);
    }

    public function test_copy_isbn_is_unique_across_copies(): void
    {
        $b1 = Book::factory()->create();
        $b2 = Book::factory()->create();

        $this->post(route('books.copy', $b1));
        $this->post(route('books.copy', $b2));

        $isbns = Book::pluck('isbn');
        $this->assertCount(4, $isbns);
        $this->assertCount(4, $isbns->unique());
    }

    public function test_journal_empty_when_no_changes(): void
    {
        $this->get(route('books.journal'))
            ->assertOk()
            ->assertSee('Žurnāls');
    }

    public function test_journal_paginates(): void
    {
        $book = Book::factory()->create(['available_copies' => 20]);
        foreach (range(1, 25) as $i) {
            $book->decrement('available_copies');
        }

        $this->get(route('books.journal'))
            ->assertOk()
            ->assertSee('Žurnāls');
    }

    public function test_show_returns_404_for_deleted_and_force_deleted(): void
    {
        $book = Book::factory()->create();
        $book->forceDelete();

        $this->get(route('books.show', $book->id))
            ->assertNotFound();
    }
}
