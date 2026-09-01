<?php

namespace App\Policies;

use App\Models\StudentPromotion;
use App\Models\User;

class StudentPromotionPolicy
{
    private function isStaff(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'director', 'coordinator']);
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, StudentPromotion $promotion): bool
    {
        if ($this->isStaff($user)) {
            return true;
        }

        if ($user->id === $promotion->student_id) {
            return true;
        }

        if ($user->hasRole('guardian')) {
            return $user->students()->where('users.id', $promotion->student_id)->exists();
        }

        return false;
    }
}
