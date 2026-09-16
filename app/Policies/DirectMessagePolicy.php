<?php

namespace App\Policies;

use App\Models\DirectMessage;
use App\Models\User;

class DirectMessagePolicy
{
    public function view(User $user, DirectMessage $message): bool
    {
        return $user->id === $message->sender_id || $user->id === $message->recipient_id;
    }

    public function create(User $user, ?User $recipient = null): bool
    {
        if (! $recipient) {
            return true;
        }

        return $this->canMessage($user, $recipient);
    }

    public function canMessage(User $sender, User $recipient): bool
    {
        if ($sender->id === $recipient->id) {
            return false;
        }

        // Staff puede escribir a cualquiera
        if ($sender->hasAnyRole(['admin', 'director', 'coordinator'])) {
            return true;
        }

        // Representante a profesores de sus hijos
        if ($sender->hasRole('guardian')) {
            if ($recipient->hasAnyRole(['admin', 'director', 'coordinator'])) {
                return true;
            }

            if ($recipient->hasRole('teacher')) {
                $studentIds = $sender->students()->pluck('users.id');

                return $recipient->subjectAssignments()
                    ->whereHas('section.enrollments', function ($query) use ($studentIds) {
                        $query->whereIn('student_id', $studentIds)
                            ->where('status', 'active');
                    })
                    ->exists();
            }
        }

        // Profesor a representantes de sus estudiantes
        if ($sender->hasRole('teacher')) {
            if ($recipient->hasAnyRole(['admin', 'director', 'coordinator'])) {
                return true;
            }

            if ($recipient->hasRole('guardian')) {
                $sectionIds = $sender->subjectAssignments()->pluck('section_id')->unique();

                return $recipient->students()
                    ->whereHas('enrollments', function ($query) use ($sectionIds) {
                        $query->whereIn('section_id', $sectionIds)
                            ->where('status', 'active');
                    })
                    ->exists();
            }
        }

        // Estudiante a profesores/coordinadores de su sección
        if ($sender->hasRole('student')) {
            $activeEnrollment = $sender->activeEnrollment();
            if (! $activeEnrollment) {
                return false;
            }

            if ($recipient->hasAnyRole(['admin', 'director', 'coordinator'])) {
                return true;
            }

            if ($recipient->hasRole('teacher')) {
                return $recipient->subjectAssignments()
                    ->where('section_id', $activeEnrollment->section_id)
                    ->exists();
            }
        }

        return false;
    }
}
