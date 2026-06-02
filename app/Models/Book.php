<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['title', 'isbn', 'available_copies', 'copied_from_id'];

    public function loans(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function comments(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function copiedFrom(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Book::class, 'copied_from_id');
    }

    public function scopeWithComments($query)
    {
        return $query->with('comments');
    }

    public function scopeAvailable($query)
    {
        return $query->where('available_copies', '>', 0);
    }

    public function scopeSearch($query, ?string $search)
    {
        if (blank($search)) return $query;

        $driver = $query->getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            return $query->whereRaw('search_vector @@ plainto_tsquery(\'simple\', ?)', [$search]);
        }

        return $query->where('title', 'like', '%' . $search . '%');
    }
}
