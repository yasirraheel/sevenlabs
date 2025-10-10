<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualNotification extends Model
{
    protected $fillable = [
        'title',
        'message',
        'image',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the image URL
     */
    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return asset('storage/notifications/' . $this->image);
        }
        return null;
    }
}