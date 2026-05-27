<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            CREATE TRIGGER IF NOT EXISTS log_books_update
            AFTER UPDATE OF available_copies ON books
            FOR EACH ROW
            BEGIN
                INSERT INTO journals (book_id, old_copies, new_copies, created_at)
                VALUES (OLD.id, OLD.available_copies, NEW.available_copies, datetime(\'now\'));
            END
        ');
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS log_books_update');
    }
};
