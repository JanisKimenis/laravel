<?php

namespace Tests\Feature;

use Tests\RefreshMongoDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FineControllerTest extends TestCase
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

    public function test_fine_index_displays_current_rate(): void
    {
        $this->get(route('fines.index'))
            ->assertOk()
            ->assertSee('Soda likme')
            ->assertSee('0.50');
    }

    public function test_fine_index_displays_form(): void
    {
        $this->get(route('fines.index'))
            ->assertOk()
            ->assertSee('Saglabāt')
            ->assertSee('EUR/dienā');
    }

    public function test_fine_update_changes_rate(): void
    {
        $this->post(route('fines.update'), ['rate' => 1.00])
            ->assertRedirect(route('fines.index'))
            ->assertSessionHas('success');

        $rate = DB::table('settings')->where('key', 'fine_per_day')->value('value');
        $this->assertEquals('1.00', $rate);
    }

    public function test_fine_update_fails_with_negative_rate(): void
    {
        $this->post(route('fines.update'), ['rate' => -1])
            ->assertSessionHasErrors('rate');
    }

    public function test_fine_update_allows_zero_rate(): void
    {
        $this->post(route('fines.update'), ['rate' => 0])
            ->assertRedirect(route('fines.index'));

        $rate = DB::table('settings')->where('key', 'fine_per_day')->value('value');
        $this->assertEquals('0.00', $rate);
    }

    public function test_fine_update_fails_with_too_large_rate(): void
    {
        $this->post(route('fines.update'), ['rate' => 101])
            ->assertSessionHasErrors('rate');
    }

    public function test_fine_update_fails_with_non_numeric_rate(): void
    {
        $this->post(route('fines.update'), ['rate' => 'abc'])
            ->assertSessionHasErrors('rate');
    }

    public function test_fine_update_rate_reflected_in_overdue_page(): void
    {
        $this->post(route('fines.update'), ['rate' => 2.00]);

        $this->get(route('loans.overdue'))
            ->assertOk()
            ->assertSee('2.00 EUR/dienā');
    }

    public function test_fine_update_with_high_precision(): void
    {
        $this->post(route('fines.update'), ['rate' => 1.2345]);

        $rate = DB::table('settings')->where('key', 'fine_per_day')->value('value');
        $this->assertEquals('1.23', $rate);
    }

    public function test_rate_at_maximum_100(): void
    {
        $this->post(route('fines.update'), ['rate' => 100.00])
            ->assertRedirect(route('fines.index'));

        $rate = DB::table('settings')->where('key', 'fine_per_day')->value('value');
        $this->assertEquals('100.00', $rate);
    }

    public function test_rate_at_minimum_0_01(): void
    {
        $this->post(route('fines.update'), ['rate' => 0.01])
            ->assertRedirect(route('fines.index'));

        $rate = DB::table('settings')->where('key', 'fine_per_day')->value('value');
        $this->assertEquals('0.01', $rate);
    }

    public function test_rate_just_over_max_fails(): void
    {
        $this->post(route('fines.update'), ['rate' => 100.01])
            ->assertSessionHasErrors('rate');
    }
}
