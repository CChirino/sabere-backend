<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentGuardian extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'guardian_id',
        'student_id',
        'relationship',
        'is_primary',
        'can_pickup',
        'emergency_contact',
        'phone',
        'status',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'can_pickup' => 'boolean',
        'emergency_contact' => 'boolean',
        'status' => 'boolean',
    ];

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Obtener el nombre de la relación en español
     */
    public function getRelationshipNameAttribute(): string
    {
        return match ($this->relationship) {
            'father' => 'Padre',
            'mother' => 'Madre',
            'guardian' => 'Representante',
            'grandparent' => 'Abuelo/a',
            'sibling' => 'Hermano/a',
            'other' => 'Otro',
            default => 'Representante',
        };
    }
}
