<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    private function isStaff(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'director', 'coordinator']);
    }

    public function viewDocuments(User $user, User $student): bool
    {
        if ($this->isStaff($user) || $user->hasRole('teacher')) {
            return true;
        }

        if ($user->id === $student->id) {
            return true;
        }

        if ($user->hasRole('guardian')) {
            return $user->students()->where('users.id', $student->id)->exists();
        }

        return false;
    }

    public function generateDocuments(User $user): bool
    {
        return $this->isStaff($user) || $user->hasRole('teacher');
    }
}
