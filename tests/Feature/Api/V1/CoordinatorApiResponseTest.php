<?php

namespace Tests\Feature\Api\V1;

use App\Models\StudentScore;
use App\Models\SubjectAssignment;
use App\Models\Term;
use Tests\TestCase;

class CoordinatorApiResponseTest extends TestCase
{
    public function test_teachers_list_uses_standard_paginated_response(): void
    {
        $coordinator = $this->createUser('coordinator');
        $teacher = $this->createUser('teacher', ['name' => 'Profesora Ana']);

        $this->actingAs($coordinator)
            ->getJson('/api/v1/coordinator/teachers?search=Ana&per_page=5')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Profesores obtenidos exitosamente')
            ->assertJsonPath('per_page', 5)
            ->assertJsonPath('data.0.id', $teacher->id)
            ->assertJsonStructure(['data', 'current_page', 'last_page', 'total']);
    }

    public function test_teacher_detail_uses_standard_success_and_error_responses(): void
    {
        $coordinator = $this->createUser('coordinator');
        $teacher = $this->createUser('teacher');

        $this->actingAs($coordinator)
            ->getJson("/api/v1/coordinator/teachers/{$teacher->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Profesor obtenido exitosamente')
            ->assertJsonPath('data.id', $teacher->id)
            ->assertJsonStructure(['data' => ['stats', 'assignments']]);

        $this->actingAs($coordinator)
            ->getJson('/api/v1/coordinator/teachers/999999')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Profesor no encontrado');
    }

    public function test_tasks_overview_uses_standard_response(): void
    {
        $coordinator = $this->createUser('coordinator');

        $this->actingAs($coordinator)
            ->getJson('/api/v1/coordinator/tasks-overview?per_page=5')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Resumen de tareas obtenido exitosamente')
            ->assertJsonPath('data.pagination.per_page', 5)
            ->assertJsonStructure(['data' => ['items', 'stats', 'pagination']]);
    }

    public function test_scores_overview_uses_standard_response(): void
    {
        $coordinator = $this->createUser('coordinator');
        $term = Term::factory()->create();
        $assignment = SubjectAssignment::factory()->create();
        StudentScore::factory()->create([
            'subject_assignment_id' => $assignment->id,
            'term_id' => $term->id,
            'score' => 8,
        ]);
        StudentScore::factory()->create([
            'subject_assignment_id' => $assignment->id,
            'term_id' => $term->id,
            'score' => 14,
        ]);

        $this->actingAs($coordinator)
            ->getJson("/api/v1/coordinator/scores-overview?per_page=5&term_id={$term->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Resumen de notas obtenido exitosamente')
            ->assertJsonPath('data.pagination.per_page', 5)
            ->assertJsonPath('data.items.0.scores_entered', 2)
            ->assertJsonPath('data.items.0.average_score', 11)
            ->assertJsonPath('data.stats.students_below_passing', 1)
            ->assertJsonStructure(['data' => ['items', 'stats', 'pagination']]);
    }
}
