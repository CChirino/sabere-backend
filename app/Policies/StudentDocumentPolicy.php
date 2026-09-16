<?php

namespace App\Policies;

use App\Models\StudentDocument;
use App\Models\User;

class StudentDocumentPolicy
{
    public function view(User $user, StudentDocument $document): bool
    {
        return $this->isStaff($user) || $this->isGuardianOf($user, $document->user);
    }

    public function create(User $user, User $student): bool
    {
        return $this->isStaff($user) || $this->isGuardianOf($user, $student);
    }

    public function delete(User $user, StudentDocument $document): bool
    {
        return $this->isStaff($user) || ($this->isGuardianOf($user, $document->user) && ! $document->is_verified);
    }

    public function verify(User $user, StudentDocument $document): bool
    {
        return $this->isStaff($user);
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
