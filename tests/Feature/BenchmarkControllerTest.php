<?php

namespace Tests\Feature;

use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BenchmarkControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_benchmark_index_page_loads(): void
    {
        $this->get(route('benchmark.index'))
            ->assertOk()
            ->assertSee('Indeksu testēšana');
    }

    public function test_benchmark_run_works(): void
    {
        Book::factory()->create(['title' => 'Testa Booket']);
        Book::factory()->create(['title' => 'Vēl viena Booket']);

        $this->post(route('benchmark.run'))
            ->assertOk()
            ->assertSee('Bez indeksa')
            ->assertSee('Ar indeksu')
            ->assertSee('EXPLAIN QUERY PLAN');
    }
}
