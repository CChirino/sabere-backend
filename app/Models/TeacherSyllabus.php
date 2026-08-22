<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeacherSyllabus extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'subject_assignment_id',
        'term_id',
        'title',
        'description',
        'content_type',
        'file_path',
        'file_name',
        'file_size',
        'content',
        'objectives',
        'topics',
        'evaluation_criteria',
        'resources',
        'is_published',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'objectives' => 'array',
        'topics' => 'array',
        'evaluation_criteria' => 'array',
        'resources' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function subjectAssignment(): BelongsTo
    {
        return $this->belongsTo(SubjectAssignment::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeForSubject($query, int $subjectAssignmentId)
    {
        return $query->where('subject_assignment_id', $subjectAssignmentId);
    }

    public function scopeForTerm($query, ?int $termId)
    {
        if ($termId) {
            return $query->where('term_id', $termId);
        }

        return $query;
    }

    public function publish(): void
    {
        $this->update([
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    public function unpublish(): void
    {
        $this->update([
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    public function getFileUrlAttribute(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        return \Storage::url($this->file_path);
    }
}
