<?php

namespace App\Policies;

use App\Models\Term;
use App\Models\User;

class TermPolicy
{
    private function isStaff(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'director', 'coordinator']);
    }

    public function view(User $user, Term $term): bool
    {
        return $user->hasAnyRole(['admin', 'director', 'coordinator', 'teacher', 'student', 'guardian']);
    }

    public function create(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function update(User $user, Term $term): bool
    {
        return $this->isStaff($user);
    }

    public function delete(User $user, Term $term): bool
    {
        return $this->isStaff($user);
    }

    public function close(User $user): bool
    {
        return $this->isStaff($user);
    }
}
