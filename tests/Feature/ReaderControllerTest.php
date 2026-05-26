<?php

namespace Tests\Feature;

use App\Models\Reader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReaderControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_displays_readers(): void
    {
        Reader::factory()->create(['name' => 'Jānis Bērziņš']);

        $this->get(route('readers.index'))
            ->assertOk()
            ->assertSee('Jānis Bērziņš');
    }

    public function test_store_creates_reader(): void
    {
        $this->post(route('readers.store'), [
            'name' => 'Anna Kalniņa',
            'email' => 'anna@example.com',
        ])->assertRedirect(route('readers.index'));

        $this->assertDatabaseHas('readers', ['email' => 'anna@example.com']);
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

    public function test_destroy_deletes_reader(): void
    {
        $reader = Reader::factory()->create();

        $this->delete(route('readers.destroy', $reader))
            ->assertRedirect(route('readers.index'));

        $this->assertDatabaseMissing('readers', ['id' => $reader->id]);
    }
}
