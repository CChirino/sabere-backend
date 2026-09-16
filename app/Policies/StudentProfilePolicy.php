<?php

namespace App\Policies;

use App\Models\User;

class StudentProfilePolicy
{
    public function view(User $user, User $student): bool
    {
        return $this->isStaff($user) || $this->isGuardianOf($user, $student);
    }

    public function update(User $user, User $student): bool
    {
        return $this->isStaff($user) || $this->isGuardianOf($user, $student);
    }

    private function isStaff(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'director', 'coordinator']);
    }

    private function isGuardianOf(User $guardian, User $student): bool
    {
        return $guardian->hasRole('guardian') && $guardian->students()->where('users.id', $student->id)->exists();
    }
}
