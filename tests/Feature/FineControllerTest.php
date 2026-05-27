<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FineControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('settings')->updateOrInsert(
            ['key' => 'fine_per_day'],
            ['value' => '0.50', 'created_at' => now(), 'updated_at' => now()]
        );
    }

    public function test_fine_index_displays_current_rate(): void
    {
        $this->get(route('fines.index'))
            ->assertOk()
            ->assertSee('Soda likme')
            ->assertSee('0.50');
    }

    public function test_fine_update_changes_rate(): void
    {
        $this->post(route('fines.update'), ['rate' => 1.00])
            ->assertRedirect(route('fines.index'))
            ->assertSessionHas('success');

        $rate = DB::table('settings')->where('key', 'fine_per_day')->value('value');
        $this->assertEquals('1.00', $rate);
    }
}
