<?php

namespace App\Services;

use App\Models\AcademicPeriod;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\SubjectAssignment;
use App\Models\Term;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SchoolYearService
{
    /**
     * Crear un nuevo año escolar copiando la estructura del período fuente.
     *
     * @return array{period: AcademicPeriod, terms: int, sections: int, assignments: int, schedules: int}
     */
    public function startYear(
        string $schoolYear,
        AcademicPeriod $source,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        return DB::transaction(function () use ($schoolYear, $source, $startDate, $endDate) {
            // 1. Crear período académico
            $period = AcademicPeriod::create([
                'name' => "Año Escolar {$schoolYear}",
                'code' => $schoolYear,
                'school_year' => $schoolYear,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => true,
            ]);

            // 2. Crear 3 lapsos distribuyendo fechas equitativamente
            $termsCreated = $this->createTerms($period, $startDate, $endDate);

            // 3. Copiar secciones
            $sectionMap = $this->copySections($source, $period);

            // 4. Copiar asignaciones profesor-materia-sección
            $assignmentMap = $this->copyAssignments($source, $period, $sectionMap);

            // 5. Copiar horarios
            $schedulesCreated = $this->copySchedules($source, $assignmentMap);

            return [
                'period' => $period,
                'terms' => $termsCreated,
                'sections' => count($sectionMap),
                'assignments' => count($assignmentMap),
                'schedules' => $schedulesCreated,
            ];
        });
    }

    private function createTerms(AcademicPeriod $period, Carbon $startDate, Carbon $endDate): int
    {
        $totalDays = $startDate->diffInDays($endDate);
        $termDays = intval($totalDays / 3);
        $weights = [33.33, 33.33, 33.34];

        for ($i = 1; $i <= 3; $i++) {
            $termStart = $startDate->copy()->addDays(($i - 1) * $termDays);
            $termEnd = $i === 3
                ? $endDate->copy()
                : $startDate->copy()->addDays($i * $termDays)->subDay();

            Term::create([
                'academic_period_id' => $period->id,
                'name' => "Lapso {$i}",
                'number' => $i,
                'start_date' => $termStart->format('Y-m-d'),
                'end_date' => $termEnd->format('Y-m-d'),
                'weight' => $weights[$i - 1],
                'status' => true,
                'is_closed' => false,
            ]);
        }

        return 3;
    }

    /**
     * @return array<int, int> Mapa de section_id antiguo => section_id nuevo
     */
    private function copySections(AcademicPeriod $source, AcademicPeriod $target): array
    {
        $sectionMap = [];

        $sourceSections = Section::where('academic_period_id', $source->id)
            ->whereNull('deleted_at')
            ->get();

        foreach ($sourceSections as $oldSection) {
            $newSection = Section::create([
                'grade_id' => $oldSection->grade_id,
                'academic_period_id' => $target->id,
                'name' => $oldSection->name,
                'capacity' => $oldSection->capacity,
                'status' => true,
            ]);

            $sectionMap[$oldSection->id] = $newSection->id;
        }

        return $sectionMap;
    }

    /**
     * @return array<int, int> Mapa de assignment_id antiguo => assignment_id nuevo
     */
    private function copyAssignments(AcademicPeriod $source, AcademicPeriod $target, array $sectionMap): array
    {
        $assignmentMap = [];

        $sourceAssignments = SubjectAssignment::where('academic_period_id', $source->id)
            ->whereNull('deleted_at')
            ->get();

        foreach ($sourceAssignments as $oldAssignment) {
            $newSectionId = $sectionMap[$oldAssignment->section_id] ?? null;

            if (! $newSectionId) {
                continue;
            }

            $newAssignment = SubjectAssignment::create([
                'teacher_id' => $oldAssignment->teacher_id,
                'subject_id' => $oldAssignment->subject_id,
                'section_id' => $newSectionId,
                'academic_period_id' => $target->id,
                'status' => true,
            ]);

            $assignmentMap[$oldAssignment->id] = $newAssignment->id;
        }

        return $assignmentMap;
    }

    private function copySchedules(AcademicPeriod $source, array $assignmentMap): int
    {
        $count = 0;

        $sourceAssignmentIds = array_keys($assignmentMap);

        if (empty($sourceAssignmentIds)) {
            return 0;
        }

        $schedules = Schedule::whereIn('subject_assignment_id', $sourceAssignmentIds)
            ->whereNull('deleted_at')
            ->get();

        foreach ($schedules as $oldSchedule) {
            $newAssignmentId = $assignmentMap[$oldSchedule->subject_assignment_id] ?? null;

            if (! $newAssignmentId) {
                continue;
            }

            Schedule::create([
                'subject_assignment_id' => $newAssignmentId,
                'day_of_week' => $oldSchedule->day_of_week,
                'start_time' => $oldSchedule->start_time,
                'end_time' => $oldSchedule->end_time,
                'classroom' => $oldSchedule->classroom,
                'notes' => $oldSchedule->notes,
                'status' => true,
            ]);

            $count++;
        }

        return $count;
    }
}
