<?php

namespace Database\Seeders;

use App\Models\Book;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookSeeder extends Seeder
{
    public function run(): void
    {
        $count = 5000;
        $batchSize = 500;
        $now = now();

        for ($i = 0; $i < $count; $i += $batchSize) {
            $books = [];
            $size = min($batchSize, $count - $i);
            for ($j = 0; $j < $size; $j++) {
                $books[] = [
                    'title' => fake()->sentence(3),
                    'isbn' => fake()->unique()->isbn13(),
                    'available_copies' => fake()->numberBetween(0, 10),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('books')->insert($books);
        }
    }
}
