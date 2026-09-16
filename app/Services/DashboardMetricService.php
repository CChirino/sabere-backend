<?php

namespace App\Services;

use App\Models\AcademicPeriod;
use App\Models\Attendance;
use App\Models\DisciplinaryRecord;
use App\Models\Enrollment;
use App\Models\Justification;
use App\Models\Section;
use App\Models\StudentApplication;
use App\Models\StudentScore;
use App\Models\SubjectAssignment;
use Illuminate\Support\Facades\DB;

class DashboardMetricService
{
    public function all(?int $academicPeriodId = null): array
    {
        $period = $this->resolvePeriod($academicPeriodId);
        $periodId = $period?->id;

        return [
            'academic_period' => $period,
            'academic_period_id' => $periodId,
            'attendance_by_section' => $periodId ? $this->attendanceBySection($periodId) : [],
            'scores_by_section' => $periodId ? $this->scoresBySection($periodId) : [],
            'scores_by_subject' => $periodId ? $this->scoresBySubject($periodId) : [],
            'at_risk_students' => $periodId ? $this->atRiskStudents($periodId) : [],
            'disciplinary_by_severity' => $periodId ? $this->disciplinaryBySeverity($periodId) : [],
            'disciplinary_by_type' => $periodId ? $this->disciplinaryByType($periodId) : [],
            'admissions_by_status' => $periodId ? $this->admissionsByStatus($periodId) : [],
            'pending_justifications' => $periodId ? $this->pendingJustifications($periodId) : 0,
        ];
    }

    private function resolvePeriod(?int $id): ?AcademicPeriod
    {
        if ($id) {
            return AcademicPeriod::find($id);
        }

        return AcademicPeriod::where('status', true)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first();
    }

    public function attendanceBySection(int $academicPeriodId): array
    {
        $rows = Attendance::where('academic_period_id', $academicPeriodId)
            ->select('section_id', 'status', DB::raw('count(*) as total'))
            ->groupBy('section_id', 'status')
            ->get();

        $grouped = $rows->groupBy('section_id')->map(function ($items) {
            $counts = $items->pluck('total', 'status');
            $present = $counts->get('present', 0);
            $late = $counts->get('late', 0);
            $excused = $counts->get('excused', 0);
            $absent = $counts->get('absent', 0);
            $total = $present + $late + $excused + $absent;

            return [
                'total' => $total,
                'attended' => $present + $late + $excused,
                'percentage' => $total > 0 ? round((($present + $late + $excused) / $total) * 100, 2) : 0,
            ];
        });

        $sections = Section::where('academic_period_id', $academicPeriodId)
            ->with('grade')
            ->get()
            ->keyBy('id');

        return $sections->map(function ($section) use ($grouped) {
            $data = $grouped->get($section->id, ['total' => 0, 'attended' => 0, 'percentage' => 0]);

            return [
                'section_id' => $section->id,
                'section_name' => $section->grade?->name.' - '.$section->name,
                'total' => $data['total'],
                'attended' => $data['attended'],
                'percentage' => $data['percentage'],
            ];
        })->sortByDesc('percentage')->values()->toArray();
    }

    public function scoresBySection(int $academicPeriodId): array
    {
        $assignmentIds = SubjectAssignment::where('academic_period_id', $academicPeriodId)
            ->pluck('id');

        $scores = StudentScore::whereIn('subject_assignment_id', $assignmentIds)
            ->select('subject_assignment_id', DB::raw('avg(score) as average'))
            ->groupBy('subject_assignment_id')
            ->get();

        $assignments = SubjectAssignment::with('section.grade')
            ->whereIn('id', $scores->pluck('subject_assignment_id'))
            ->get()
            ->keyBy('id');

        return $scores->map(function ($score) use ($assignments) {
            $assignment = $assignments->get($score->subject_assignment_id);

            return [
                'section_id' => $assignment?->section_id,
                'section_name' => $assignment?->section?->grade?->name.' - '.$assignment?->section?->name ?? 'N/A',
                'average' => round((float) $score->average, 2),
            ];
        })->toArray();
    }

    public function scoresBySubject(int $academicPeriodId): array
    {
        $assignmentIds = SubjectAssignment::where('academic_period_id', $academicPeriodId)
            ->pluck('id');

        $scores = StudentScore::whereIn('subject_assignment_id', $assignmentIds)
            ->select('subject_assignment_id', DB::raw('avg(score) as average'))
            ->groupBy('subject_assignment_id')
            ->get();

        $assignments = SubjectAssignment::with('subject')
            ->whereIn('id', $scores->pluck('subject_assignment_id'))
            ->get()
            ->keyBy('id');

        return $scores->map(function ($score) use ($assignments) {
            $assignment = $assignments->get($score->subject_assignment_id);

            return [
                'subject_id' => $assignment?->subject_id,
                'subject_name' => $assignment?->subject?->name ?? 'N/A',
                'average' => round((float) $score->average, 2),
            ];
        })->toArray();
    }

    public function atRiskStudents(int $academicPeriodId): array
    {
        $enrollments = Enrollment::where('academic_period_id', $academicPeriodId)
            ->where('status', 'active')
            ->with('student', 'section')
            ->get();

        $assignmentIds = SubjectAssignment::where('academic_period_id', $academicPeriodId)
            ->pluck('id');

        $averages = StudentScore::whereIn('subject_assignment_id', $assignmentIds)
            ->select('student_id', DB::raw('avg(score) as average'))
            ->groupBy('student_id')
            ->pluck('average', 'student_id');

        return $enrollments->map(function ($enrollment) use ($averages) {
            $stats = Attendance::calculateAttendancePercentage(
                $enrollment->student_id,
                $enrollment->section_id,
                $enrollment->academic_period_id
            );

            $average = $averages->get($enrollment->student_id);

            return [
                'student_id' => $enrollment->student_id,
                'student_name' => $enrollment->student?->name,
                'section_name' => $enrollment->section?->name,
                'attendance_percentage' => $stats['percentage'],
                'score_average' => $average ? round((float) $average, 2) : null,
            ];
        })->filter(function ($student) {
            return $student['attendance_percentage'] < 75 || ($student['score_average'] !== null && $student['score_average'] < 10);
        })->values()->toArray();
    }

    public function disciplinaryBySeverity(int $academicPeriodId): array
    {
        $counts = DisciplinaryRecord::where('academic_period_id', $academicPeriodId)
            ->select('severity', DB::raw('count(*) as total'))
            ->groupBy('severity')
            ->pluck('total', 'severity');

        return [
            'leve' => (int) $counts->get('leve', 0),
            'moderada' => (int) $counts->get('moderada', 0),
            'grave' => (int) $counts->get('grave', 0),
        ];
    }

    public function disciplinaryByType(int $academicPeriodId): array
    {
        $records = DisciplinaryRecord::with('incidentType')
            ->where('academic_period_id', $academicPeriodId)
            ->select('incident_type_id', DB::raw('count(*) as total'))
            ->groupBy('incident_type_id')
            ->get();

        return $records->map(function ($record) {
            $type = $record->incidentType;

            return [
                'incident_type_id' => $record->incident_type_id,
                'incident_type_name' => $type?->name ?? 'Otro',
                'total' => (int) $record->total,
            ];
        })->toArray();
    }

    public function admissionsByStatus(int $academicPeriodId): array
    {
        $counts = StudentApplication::where('academic_period_id', $academicPeriodId)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'pending' => (int) $counts->get('pending', 0),
            'approved' => (int) $counts->get('approved', 0),
            'rejected' => (int) $counts->get('rejected', 0),
        ];
    }

    public function pendingJustifications(int $academicPeriodId): int
    {
        return Justification::where('academic_period_id', $academicPeriodId)
            ->where('status', 'pending')
            ->count();
    }
}
