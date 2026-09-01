<?php

namespace App\Policies;

use App\Models\EvaluationPlan;
use App\Models\User;

class EvaluationPlanPolicy
{
    private function isStaff(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'director', 'coordinator']);
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, EvaluationPlan $plan): bool
    {
        if ($this->isStaff($user)) {
            return true;
        }

        if ($user->hasRole('teacher')) {
            return $this->teachesPlan($user, $plan);
        }

        if ($user->hasRole('student')) {
            return $this->isStudentPlan($user, $plan);
        }

        if ($user->hasRole('guardian')) {
            return $this->isGuardianPlan($user, $plan);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $this->isStaff($user) || $user->hasRole('teacher');
    }

    public function update(User $user, EvaluationPlan $plan): bool
    {
        if ($this->isStaff($user)) {
            return true;
        }

        return $user->hasRole('teacher') && $this->teachesPlan($user, $plan);
    }

    public function delete(User $user, EvaluationPlan $plan): bool
    {
        return $this->update($user, $plan);
    }

    public function submit(User $user, EvaluationPlan $plan): bool
    {
        return $user->hasRole('teacher') && $this->teachesPlan($user, $plan);
    }

    public function approve(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function reject(User $user): bool
    {
        return $this->approve($user);
    }

    private function teachesPlan(User $user, EvaluationPlan $plan): bool
    {
        $query = $user->subjectAssignments()
            ->where('academic_period_id', $plan->academic_period_id)
            ->where('subject_id', $plan->subject_id);

        if ($plan->section_id) {
            $query->where('section_id', $plan->section_id);
        }

        return $query->exists();
    }

    private function isStudentPlan(User $user, EvaluationPlan $plan): bool
    {
        return $user->enrollments()
            ->where('academic_period_id', $plan->academic_period_id)
            ->whereHas('section', function ($q) use ($plan) {
                $q->where('grade_id', $plan->grade_id);
                if ($plan->section_id) {
                    $q->where('id', $plan->section_id);
                }
            })
            ->exists();
    }

    private function isGuardianPlan(User $user, EvaluationPlan $plan): bool
    {
        $studentIds = $user->students()->pluck('users.id');

        return \App\Models\Enrollment::whereIn('student_id', $studentIds)
            ->where('academic_period_id', $plan->academic_period_id)
            ->whereHas('section', function ($q) use ($plan) {
                $q->where('grade_id', $plan->grade_id);
                if ($plan->section_id) {
                    $q->where('id', $plan->section_id);
                }
            })
            ->exists();
    }
}
