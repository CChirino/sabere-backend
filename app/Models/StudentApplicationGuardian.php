<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentApplicationGuardian extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_application_id',
        'first_name',
        'last_name',
        'id_number',
        'email',
        'phone',
        'relationship',
        'is_primary',
        'address',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(StudentApplication::class);
    }
}
