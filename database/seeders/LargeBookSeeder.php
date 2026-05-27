<?php

namespace Database\Seeders;

use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LargeBookSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $total = 10000;
        $chunkSize = 500;
        $now = now();

        $existingCount = DB::table('books')->count();
        $start = $existingCount + 1;

        $books = [];
        for ($i = $start; $i <= $start + $total - 1; $i++) {
            $books[] = [
                'title' => $faker->sentence(3) . ' Booket',
                'isbn' => '978-' . str_pad((string) $i, 12, '0', STR_PAD_LEFT),
                'available_copies' => $faker->numberBetween(0, 10),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($books) >= $chunkSize) {
                DB::table('books')->insert($books);
                $books = [];
            }
        }

        if (!empty($books)) {
            DB::table('books')->insert($books);
        }
    }
}
