<?php

namespace App\Policies;

use App\Models\IncidentType;
use App\Models\User;

class IncidentTypePolicy
{
    private function isStaff(User $user): bool
    {
        return $user->hasRole(['admin', 'director', 'coordinator']);
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'director', 'coordinator', 'teacher']);
    }

    public function view(User $user, IncidentType $incidentType): bool
    {
        return $user->hasRole(['admin', 'director', 'coordinator', 'teacher']);
    }

    public function create(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function update(User $user, IncidentType $incidentType): bool
    {
        return $this->isStaff($user);
    }

    public function delete(User $user, IncidentType $incidentType): bool
    {
        return $this->isStaff($user);
    }
}
