<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumThread extends Model
{
    protected $fillable = [
        'category_id', 'user_id', 'title', 'body', 'external_id', 'media_type',
    ];

    protected function casts(): array
    {
        return [
            'is_locked' => 'boolean',
            'is_hidden' => 'boolean',
            'external_id' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ForumCategory::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ForumReply::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(ForumVote::class);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false);
    }
}
