<?php

namespace Tests\Feature;

use App\Models\Reader;
use Tests\RefreshMongoDatabase;
use Tests\TestCase;

class ReaderControllerTest extends TestCase
{
    use RefreshMongoDatabase;

    public function test_index_displays_readers(): void
    {
        Reader::factory()->create(['name' => 'Jānis Bērziņš']);

        $this->get(route('readers.index'))
            ->assertOk()
            ->assertSee('Jānis Bērziņš');
    }

    public function test_create_page_displays_form(): void
    {
        $this->get(route('readers.create'))
            ->assertOk()
            ->assertSee('Pievienot lasītāju')
            ->assertSee('Vārds')
            ->assertSee('E-pasts');
    }

    public function test_edit_page_displays_form(): void
    {
        $reader = Reader::factory()->create(['name' => 'Rediģējamais']);
        $this->get(route('readers.edit', $reader))
            ->assertOk()
            ->assertSee('Labot lasītāju')
            ->assertSee('Vārds')
            ->assertSee('E-pasts');
    }

    public function test_edit_returns_404_for_nonexistent_reader(): void
    {
        $this->get(route('readers.edit', 999999))
            ->assertNotFound();
    }

    public function test_store_creates_reader(): void
    {
        $this->post(route('readers.store'), [
            'name' => 'Anna Kalniņa',
            'email' => 'anna@example.com',
        ])->assertRedirect(route('readers.index'));

        $this->assertDatabaseHas('readers', ['email' => 'anna@example.com']);
    }

    public function test_store_fails_without_name(): void
    {
        $this->post(route('readers.store'), [
            'email' => 'test@example.com',
        ])->assertSessionHasErrors('name');
    }

    public function test_store_fails_without_email(): void
    {
        $this->post(route('readers.store'), [
            'name' => 'Vārds',
        ])->assertSessionHasErrors('email');
    }

    public function test_store_fails_with_invalid_email(): void
    {
        $this->post(route('readers.store'), [
            'name' => 'Vārds',
            'email' => 'nav-epasts',
        ])->assertSessionHasErrors('email');
    }

    public function test_store_fails_with_duplicate_email(): void
    {
        Reader::factory()->create(['email' => 'dup@example.com']);

        $this->post(route('readers.store'), [
            'name' => 'Cits Vārds',
            'email' => 'dup@example.com',
        ])->assertSessionHasErrors('email');
    }

    public function test_update_modifies_reader(): void
    {
        $reader = Reader::factory()->create(['name' => 'Vecs Vārds']);

        $this->put(route('readers.update', $reader), [
            'name' => 'Jauns Vārds',
            'email' => $reader->email,
        ])->assertRedirect(route('readers.index'));

        $this->assertDatabaseHas('readers', ['name' => 'Jauns Vārds']);
    }

    public function test_update_fails_without_name(): void
    {
        $reader = Reader::factory()->create();

        $this->put(route('readers.update', $reader), [
            'email' => 'test@example.com',
        ])->assertSessionHasErrors('name');
    }

    public function test_update_fails_without_email(): void
    {
        $reader = Reader::factory()->create();

        $this->put(route('readers.update', $reader), [
            'name' => 'Vārds',
        ])->assertSessionHasErrors('email');
    }

    public function test_update_fails_with_duplicate_email(): void
    {
        Reader::factory()->create(['email' => 'first@example.com']);
        $reader = Reader::factory()->create(['email' => 'second@example.com']);

        $this->put(route('readers.update', $reader), [
            'name' => 'Vārds',
            'email' => 'first@example.com',
        ])->assertSessionHasErrors('email');
    }

    public function test_update_allows_same_email_for_same_reader(): void
    {
        $reader = Reader::factory()->create(['email' => 'mine@example.com']);

        $this->put(route('readers.update', $reader), [
            'name' => 'Jauns Vārds',
            'email' => 'mine@example.com',
        ])->assertRedirect(route('readers.index'));
    }

    public function test_destroy_deletes_reader(): void
    {
        $reader = Reader::factory()->create();

        $this->delete(route('readers.destroy', $reader))
            ->assertRedirect(route('readers.index'));

        $this->assertDatabaseMissing('readers', ['id' => $reader->id]);
    }

    public function test_destroy_returns_404_for_nonexistent_reader(): void
    {
        $this->delete(route('readers.destroy', 999999))
            ->assertNotFound();
    }

    public function test_index_paginates_when_many_readers(): void
    {
        Reader::factory()->count(15)->create();

        $this->get(route('readers.index'))
            ->assertOk()
            ->assertSee('Lasītāji');
    }

    public function test_store_with_max_length_name(): void
    {
        $name = str_repeat('Ā', 255);

        $this->post(route('readers.store'), [
            'name' => $name,
            'email' => 'max@example.com',
        ])->assertRedirect(route('readers.index'));

        $this->assertDatabaseHas('readers', ['email' => 'max@example.com']);
    }

    public function test_store_fails_with_name_too_long(): void
    {
        $this->post(route('readers.store'), [
            'name' => str_repeat('Ā', 256),
            'email' => 'long@example.com',
        ])->assertSessionHasErrors('name');
    }

    public function test_store_fails_with_empty_name(): void
    {
        $this->post(route('readers.store'), [
            'name' => '',
            'email' => 'empty@example.com',
        ])->assertSessionHasErrors('name');
    }

    public function test_store_fails_with_empty_email(): void
    {
        $this->post(route('readers.store'), [
            'name' => 'Test',
            'email' => '',
        ])->assertSessionHasErrors('email');
    }

    public function test_update_with_same_data_succeeds(): void
    {
        $reader = Reader::factory()->create([
            'name' => 'Nemainīgs Lasītājs',
            'email' => 'same@example.com',
        ]);

        $this->put(route('readers.update', $reader), [
            'name' => 'Nemainīgs Lasītājs',
            'email' => 'same@example.com',
        ])->assertRedirect(route('readers.index'));

        $this->assertDatabaseHas('readers', ['id' => $reader->id, 'name' => 'Nemainīgs Lasītājs']);
    }

    public function test_store_redirects_with_success_message(): void
    {
        $this->post(route('readers.store'), [
            'name' => 'Veiksmīgs',
            'email' => 'success@example.com',
        ])->assertRedirect(route('readers.index'))
          ->assertSessionHas('success');
    }

    public function test_destroy_redirects_with_success_message(): void
    {
        $reader = Reader::factory()->create();

        $this->delete(route('readers.destroy', $reader))
            ->assertRedirect(route('readers.index'))
            ->assertSessionHas('success');
    }

    public function test_update_redirects_with_success_message(): void
    {
        $reader = Reader::factory()->create();

        $this->put(route('readers.update', $reader), [
            'name' => 'Atjaunots',
            'email' => $reader->email,
        ])->assertRedirect(route('readers.index'))
          ->assertSessionHas('success');
    }

    public function test_index_shows_all_readers(): void
    {
        Reader::factory()->create(['name' => 'Pirmais']);
        Reader::factory()->create(['name' => 'Otrais']);

        $this->get(route('readers.index'))
            ->assertOk()
            ->assertSee('Pirmais')
            ->assertSee('Otrais');
    }

    public function test_store_fails_with_email_too_long(): void
    {
        $this->post(route('readers.store'), [
            'name' => 'Test',
            'email' => str_repeat('x', 250) . '@example.com',
        ])->assertSessionHasErrors('email');
    }
}
