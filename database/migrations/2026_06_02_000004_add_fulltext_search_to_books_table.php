<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("
                ALTER TABLE books ADD COLUMN search_vector tsvector
                GENERATED ALWAYS AS (to_tsvector('simple', coalesce(title, ''))) STORED
            ");
            DB::statement('CREATE INDEX books_search_vector_idx ON books USING GIN (search_vector)');
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS books_search_vector_idx');
            DB::statement('ALTER TABLE books DROP COLUMN IF EXISTS search_vector');
        }
    }
};
