<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForumVote extends Model
{
    public $timestamps = false;

    protected $fillable = ['thread_id', 'user_id'];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ForumThread::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
