<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCredits extends Model
{
    protected $fillable = [
        'user_id',
        'credits',
        'used_credits'
    ];

    protected $casts = [
        'credits' => 'integer',
        'used_credits' => 'integer'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getAvailableCreditsAttribute(): int
    {
        return $this->credits - $this->used_credits;
    }

    public function addCredits(int $amount): void
    {
        $this->increment('credits', $amount);
    }

    public function useCredits(int $amount): bool
    {
        if ($this->available_credits >= $amount) {
            $this->increment('used_credits', $amount);
            return true;
        }
        return false;
    }
}
