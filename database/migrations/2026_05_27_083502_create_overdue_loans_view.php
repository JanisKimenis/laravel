<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            CREATE VIEW IF NOT EXISTS overdue_loans AS
            SELECT
                loans.id AS loan_id,
                books.title AS book_title,
                readers.name AS reader_name,
                readers.email AS reader_email,
                loans.borrowed_at,
                loans.returned_at,
                (julianday(\'now\') - julianday(loans.borrowed_at)) AS days_overdue
            FROM loans
            JOIN books ON loans.book_id = books.id
            JOIN readers ON loans.reader_id = readers.id
            WHERE loans.returned_at IS NULL
              AND loans.borrowed_at < datetime(\'now\', \'-14 days\')
            ORDER BY loans.borrowed_at ASC
        ');
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS overdue_loans');
    }
};
