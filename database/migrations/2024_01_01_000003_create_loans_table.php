<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reader_id')->constrained()->cascadeOnDelete();
            $table->dateTime('borrowed_at');
            $table->dateTime('returned_at')->nullable();
            $table->timestamps();

            $table->index(['book_id', 'reader_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
