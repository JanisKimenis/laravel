<?php

namespace App\Models;

use App\Models\Scopes\ValidCommentScope;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = ['body', 'commentable_id', 'commentable_type'];

    protected static function booted(): void
    {
        static::addGlobalScope(new ValidCommentScope);
    }

    public function commentable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }
}
