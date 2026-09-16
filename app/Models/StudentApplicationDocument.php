<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class StudentApplicationDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_application_id',
        'type',
        'path',
        'original_name',
        'mime',
        'description',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(StudentApplication::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->path);
    }
}
