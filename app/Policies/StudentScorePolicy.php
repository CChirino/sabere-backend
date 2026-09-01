<?php

namespace App\Policies;

use App\Models\StudentScore;
use App\Models\SubjectAssignment;
use App\Models\User;

class StudentScorePolicy
{
    /**
     * Admin, director y coordinador pueden gestionar todas las calificaciones.
     */
    private function isStaff(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'director', 'coordinator']);
    }

    /**
     * Determinar si el usuario puede ver la calificación.
     */
    public function view(User $user, StudentScore $score): bool
    {
        if ($this->isStaff($user)) {
            return true;
        }

        // Profesor: solo si es el profesor de la asignatura
        if ($user->hasRole('teacher')) {
            return $score->subjectAssignment->teacher_id === $user->id;
        }

        // Estudiante: solo la suya propia
        if ($user->hasRole('student')) {
            return $score->student_id === $user->id;
        }

        // Representante: solo si está vinculado al estudiante
        if ($user->hasRole('guardian')) {
            return $user->students()->where('student_id', $score->student_id)->exists();
        }

        return false;
    }

    /**
     * Determinar si el usuario puede crear calificaciones.
     */
    public function create(User $user, ?SubjectAssignment $assignment = null): bool
    {
        if ($this->isStaff($user)) {
            return true;
        }

        if (! $user->hasRole('teacher') || ! $assignment) {
            return false;
        }

        return $assignment->teacher_id === $user->id;
    }

    /**
     * Determinar si el usuario puede actualizar la calificación.
     */
    public function update(User $user, StudentScore $score): bool
    {
        if ($this->isStaff($user)) {
            return true;
        }

        if (! $user->hasRole('teacher')) {
            return false;
        }

        return $score->subjectAssignment->teacher_id === $user->id;
    }

    /**
     * Determinar si el usuario puede eliminar la calificación.
     */
    public function delete(User $user, StudentScore $score): bool
    {
        // Solo admin/director/coordinator o el profesor de la materia
        if ($this->isStaff($user)) {
            return true;
        }

        if (! $user->hasRole('teacher')) {
            return false;
        }

        return $score->subjectAssignment->teacher_id === $user->id;
    }

    /**
     * Ver calificaciones de un estudiante específico.
     */
    public function viewByStudent(User $user, int $studentId): bool
    {
        if ($this->isStaff($user) || $user->hasRole('teacher')) {
            return true;
        }

        if ($user->hasRole('student')) {
            return $user->id === $studentId;
        }

        if ($user->hasRole('guardian')) {
            return $user->students()->where('student_id', $studentId)->exists();
        }

        return false;
    }

    /**
     * Ver boletín de calificaciones de un estudiante.
     */
    public function reportCard(User $user, int $studentId): bool
    {
        return $this->viewByStudent($user, $studentId);
    }
}
