<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CircularRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'circular_id',
        'user_id',
        'email_sent',
        'push_sent',
        'read_at',
    ];

    protected $casts = [
        'email_sent' => 'boolean',
        'push_sent' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function circular(): BelongsTo
    {
        return $this->belongsTo(Circular::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsRead(): void
    {
        if (is_null($this->read_at)) {
            $this->update(['read_at' => now()]);
        }
    }
}
