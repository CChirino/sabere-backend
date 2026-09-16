<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'push_scores',
        'push_tasks',
        'push_circulars',
        'push_events',
        'push_reenrollment',
        'push_messages',
    ];

    protected $casts = [
        'push_scores' => 'boolean',
        'push_tasks' => 'boolean',
        'push_circulars' => 'boolean',
        'push_events' => 'boolean',
        'push_reenrollment' => 'boolean',
        'push_messages' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function preferencesForUser(User $user): self
    {
        return static::firstOrCreate(
            ['user_id' => $user->id],
            ['user_id' => $user->id]
        );
    }

    public function acceptsPush(string $type): bool
    {
        return match ($type) {
            'score' => $this->push_scores,
            'task' => $this->push_tasks,
            'circular' => $this->push_circulars,
            'event' => $this->push_events,
            'reenrollment' => $this->push_reenrollment,
            'message' => $this->push_messages,
            default => false,
        };
    }
}
