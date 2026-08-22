<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Circular extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'content',
        'priority',
        'audience',
        'academic_period_id',
        'send_email',
        'send_push',
        'scheduled_at',
        'sent_at',
        'created_by',
    ];

    protected $casts = [
        'send_email' => 'boolean',
        'send_push' => 'boolean',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CircularRecipient::class);
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('sent_at');
    }

    public function scopeScheduled($query)
    {
        return $query->whereNull('sent_at')->whereNotNull('scheduled_at');
    }

    public function scopeDraft($query)
    {
        return $query->whereNull('sent_at')->whereNull('scheduled_at');
    }

    public function scopeForAudience($query, string $role)
    {
        return $query->where(function ($q) use ($role) {
            $q->where('audience', 'all')
                ->orWhere('audience', $role);
        });
    }

    public function isSent(): bool
    {
        return ! is_null($this->sent_at);
    }

    public function markAsSent(): void
    {
        $this->update(['sent_at' => now()]);
    }
}
