<?php

namespace App\Policies;

use App\Models\Section;
use App\Models\User;

class SectionPolicy
{
    private function isStaff(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'director', 'coordinator']);
    }

    public function view(User $user, Section $section): bool
    {
        return $user->hasAnyRole(['admin', 'director', 'coordinator', 'teacher', 'student', 'guardian']);
    }

    public function create(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function update(User $user, Section $section): bool
    {
        return $this->isStaff($user);
    }

    public function delete(User $user, Section $section): bool
    {
        return $this->isStaff($user);
    }

    public function viewStudents(User $user, Section $section): bool
    {
        return $this->view($user, $section);
    }

    public function viewSubjects(User $user, Section $section): bool
    {
        return $this->view($user, $section);
    }
}
