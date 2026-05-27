<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('
                CREATE TRIGGER IF NOT EXISTS log_books_update
                AFTER UPDATE OF available_copies ON books
                FOR EACH ROW
                BEGIN
                    INSERT INTO journals (book_id, old_copies, new_copies, created_at)
                    VALUES (OLD.id, OLD.available_copies, NEW.available_copies, datetime(\'now\'));
                END
            ');
        } elseif ($driver === 'pgsql') {
            DB::statement('
                CREATE OR REPLACE FUNCTION log_books_update_function()
                RETURNS TRIGGER AS $$
                BEGIN
                    INSERT INTO journals (book_id, old_copies, new_copies, created_at)
                    VALUES (OLD.id, OLD.available_copies, NEW.available_copies, NOW());
                    RETURN NEW;
                END;
                $$ LANGUAGE plpgsql
            ');

            DB::statement('
                CREATE TRIGGER log_books_update
                AFTER UPDATE ON books
                FOR EACH ROW
                WHEN (OLD.available_copies IS DISTINCT FROM NEW.available_copies)
                EXECUTE FUNCTION log_books_update_function()
            ');
        }
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS log_books_update ON books');
        DB::statement('DROP FUNCTION IF EXISTS log_books_update_function');
    }
};
