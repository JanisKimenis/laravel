<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    protected $fillable = ['book_id', 'old_copies', 'new_copies'];

    public $timestamps = false;

    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
