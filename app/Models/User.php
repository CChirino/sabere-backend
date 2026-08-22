<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, LogsActivity, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'provider',
        'provider_id',
        'email_verified_at',
        'cedula',
        'phone',
        'birth_date',
        'avatar',
        'bio',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'birth_date' => 'date',
    ];

    /**
     * Obtener la URL del avatar.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar) {
            return null;
        }

        return Storage::url($this->avatar);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // ==================== RELACIONES ESTUDIANTE ====================

    /**
     * Inscripciones del estudiante.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'student_id');
    }

    /**
     * Entregas de tareas del estudiante.
     */
    public function taskSubmissions(): HasMany
    {
        return $this->hasMany(TaskSubmission::class, 'student_id');
    }

    /**
     * Calificaciones del estudiante.
     */
    public function scores(): HasMany
    {
        return $this->hasMany(StudentScore::class, 'student_id');
    }

    /**
     * Representantes del estudiante.
     */
    public function guardians()
    {
        return $this->belongsToMany(User::class, 'student_guardians', 'student_id', 'guardian_id')
            ->withPivot(['relationship', 'is_primary', 'can_pickup', 'emergency_contact', 'phone', 'status'])
            ->withTimestamps();
    }

    // ==================== RELACIONES PROFESOR ====================

    /**
     * Asignaciones de materias del profesor.
     */
    public function subjectAssignments(): HasMany
    {
        return $this->hasMany(SubjectAssignment::class, 'teacher_id');
    }

    /**
     * Tareas creadas por el profesor (a través de asignaciones).
     */
    public function createdTasks(): HasManyThrough
    {
        return $this->hasManyThrough(
            Task::class,
            SubjectAssignment::class,
            'teacher_id',
            'subject_assignment_id',
            'id',
            'id'
        );
    }

    // ==================== RELACIONES REPRESENTANTE ====================

    /**
     * Estudiantes representados.
     */
    public function students()
    {
        return $this->belongsToMany(User::class, 'student_guardians', 'guardian_id', 'student_id')
            ->withPivot(['relationship', 'is_primary', 'can_pickup', 'emergency_contact', 'phone', 'status'])
            ->withTimestamps();
    }

    // ==================== HELPERS ====================

    /**
     * Obtener la inscripción activa del estudiante.
     */
    public function activeEnrollment()
    {
        return $this->enrollments()->where('status', 'active')->latest()->first();
    }

    /**
     * Verificar si el usuario es estudiante.
     */
    public function isStudent(): bool
    {
        return $this->hasRole('student');
    }

    /**
     * Verificar si el usuario es profesor.
     */
    public function isTeacher(): bool
    {
        return $this->hasRole('teacher');
    }

    /**
     * Verificar si el usuario es representante.
     */
    public function isGuardian(): bool
    {
        return $this->hasRole('guardian');
    }

    /**
     * Verificar si el usuario es coordinador.
     */
    public function isCoordinator(): bool
    {
        return $this->hasRole('coordinator');
    }

    /**
     * Verificar si el usuario es director.
     */
    public function isDirector(): bool
    {
        return $this->hasRole('director');
    }

    /**
     * Verificar si el usuario es administrador.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }
}
