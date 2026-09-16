<?php

namespace Tests\Feature\Api\V1;

use App\Models\AcademicPeriod;
use App\Models\DisciplinaryRecord;
use App\Models\Justification;
use App\Models\StudentApplication;
use Tests\TestCase;

class DashboardIndicatorTest extends TestCase
{
    public function test_staff_can_view_dashboard_indicators(): void
    {
        $coordinator = $this->createUser('coordinator');
        $period = AcademicPeriod::factory()->create();

        Justification::factory()->count(2)->create([
            'academic_period_id' => $period->id,
            'status' => 'pending',
        ]);

        StudentApplication::factory()->count(3)->create([
            'academic_period_id' => $period->id,
            'status' => 'approved',
        ]);

        DisciplinaryRecord::factory()->count(2)->create([
            'academic_period_id' => $period->id,
            'severity' => 'grave',
        ]);

        $this->actingAs($coordinator)
            ->getJson('/api/v1/dashboard/indicators?academic_period_id='.$period->id)
            ->assertOk()
            ->assertJsonPath('data.pending_justifications', 2)
            ->assertJsonPath('data.admissions_by_status.approved', 3)
            ->assertJsonPath('data.disciplinary_by_severity.grave', 2);
    }

    public function test_teacher_cannot_view_indicators(): void
    {
        $teacher = $this->createUser('teacher');

        $this->actingAs($teacher)
            ->getJson('/api/v1/dashboard/indicators')
            ->assertForbidden();
    }
}
