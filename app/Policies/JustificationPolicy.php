<?php

namespace App\Policies;

use App\Models\Justification;
use App\Models\User;

class JustificationPolicy
{
    private function isStaff(User $user): bool
    {
        return $user->hasRole(['admin', 'director', 'coordinator']);
    }

    public function viewAny(User $user): bool
    {
        return $this->isStaff($user) || $user->hasRole('guardian');
    }

    public function view(User $user, Justification $justification): bool
    {
        if ($this->isStaff($user) || $justification->guardian_id === $user->id) {
            return true;
        }

        if ($user->hasRole('guardian')) {
            return $user->students()->where('users.id', $justification->student_id)->exists();
        }

        if ($user->hasRole('student')) {
            return $user->id === $justification->student_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('guardian') || $this->isStaff($user);
    }

    public function update(User $user, Justification $justification): bool
    {
        return $justification->status === 'pending' && ($justification->guardian_id === $user->id || $this->isStaff($user));
    }

    public function delete(User $user, Justification $justification): bool
    {
        return $justification->guardian_id === $user->id || $this->isStaff($user);
    }

    public function review(User $user, Justification $justification): bool
    {
        return $this->isStaff($user) && $justification->status === 'pending';
    }
}
