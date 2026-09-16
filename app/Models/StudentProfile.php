<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'blood_type',
        'allergies',
        'medical_conditions',
        'medications',
        'dietary_restrictions',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'authorized_pickup',
        'additional_notes',
    ];

    protected $casts = [
        'authorized_pickup' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function studentDocuments(): HasMany
    {
        return $this->hasMany(StudentDocument::class, 'user_id', 'user_id');
    }
}
