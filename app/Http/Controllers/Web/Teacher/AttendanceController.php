<?php

namespace App\Http\Controllers\Web\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\SubjectAssignment;
use App\Models\Section;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Get attendance data for a specific section and date.
     */
    public function getSectionAttendance(Request $request, Section $section)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        $subjectAssignmentId = $request->get('subject_assignment_id');

        // Get students enrolled in this section
        $enrollments = Enrollment::with('student')
            ->where('section_id', $section->id)
            ->where('status', 'active')
            ->get();

        // Get existing attendance records for this date
        $query = Attendance::where('section_id', $section->id)
            ->where('date', $date);

        if ($subjectAssignmentId) {
            $query->where('subject_assignment_id', $subjectAssignmentId);
        }

        $attendanceRecords = $query->get()->keyBy('student_id');

        $students = $enrollments->map(function ($enrollment) use ($attendanceRecords) {
            $attendance = $attendanceRecords->get($enrollment->student_id);
            return [
                'id' => $enrollment->student_id,
                'name' => $enrollment->student->name,
                'email' => $enrollment->student->email,
                'attendance_id' => $attendance?->id,
                'status' => $attendance?->status ?? null,
                'notes' => $attendance?->notes ?? '',
            ];
        });

        return response()->json([
            'students' => $students,
            'date' => $date,
        ]);
    }

    /**
     * Store or update attendance records in bulk.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'section_id' => ['required', 'exists:sections,id'],
            'subject_assignment_id' => ['nullable', 'exists:subject_assignments,id'],
            'date' => ['required', 'date'],
            'attendances' => ['required', 'array'],
            'attendances.*.student_id' => ['required', 'exists:users,id'],
            'attendances.*.status' => ['required', 'in:present,absent,late,excused'],
            'attendances.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $section = Section::with('academicPeriod')->find($validated['section_id']);

        // Verify teacher has access to this section
        if ($validated['subject_assignment_id']) {
            $assignment = SubjectAssignment::find($validated['subject_assignment_id']);
            if ($assignment->teacher_id !== auth()->id() && !auth()->user()->hasAnyRole(['admin', 'director', 'coordinator'])) {
                return back()->withErrors(['error' => 'No tienes permiso para registrar asistencia en esta materia.']);
            }
        }

        foreach ($validated['attendances'] as $attendanceData) {
            Attendance::updateOrCreate(
                [
                    'student_id' => $attendanceData['student_id'],
                    'section_id' => $validated['section_id'],
                    'subject_assignment_id' => $validated['subject_assignment_id'],
                    'date' => $validated['date'],
                ],
                [
                    'academic_period_id' => $section->academic_period_id,
                    'recorded_by' => auth()->id(),
                    'status' => $attendanceData['status'],
                    'notes' => $attendanceData['notes'] ?? null,
                ]
            );
        }

        return back()->with('success', 'Asistencia registrada correctamente.');
    }

    /**
     * Get attendance statistics for a student.
     */
    public function getStudentStats(Request $request, $studentId)
    {
        $sectionId = $request->get('section_id');
        $academicPeriodId = $request->get('academic_period_id');
        $subjectAssignmentId = $request->get('subject_assignment_id');

        if (!$sectionId || !$academicPeriodId) {
            return response()->json(['error' => 'Se requiere section_id y academic_period_id'], 400);
        }

        $stats = Attendance::calculateAttendancePercentage(
            $studentId,
            $sectionId,
            $academicPeriodId,
            $subjectAssignmentId
        );

        return response()->json($stats);
    }

    /**
     * Get attendance report for a section.
     */
    public function getSectionReport(Request $request, Section $section)
    {
        $academicPeriodId = $request->get('academic_period_id', $section->academic_period_id);
        $subjectAssignmentId = $request->get('subject_assignment_id');

        $enrollments = Enrollment::with('student')
            ->where('section_id', $section->id)
            ->where('status', 'active')
            ->get();

        $report = $enrollments->map(function ($enrollment) use ($section, $academicPeriodId, $subjectAssignmentId) {
            $stats = Attendance::calculateAttendancePercentage(
                $enrollment->student_id,
                $section->id,
                $academicPeriodId,
                $subjectAssignmentId
            );

            return [
                'student_id' => $enrollment->student_id,
                'student_name' => $enrollment->student->name,
                'student_email' => $enrollment->student->email,
                ...$stats,
            ];
        });

        // Count students at risk (below 75%)
        $atRiskCount = $report->filter(fn($r) => !$r['meets_requirement'])->count();

        return response()->json([
            'report' => $report,
            'summary' => [
                'total_students' => $report->count(),
                'at_risk_count' => $atRiskCount,
                'passing_count' => $report->count() - $atRiskCount,
            ],
        ]);
    }

    /**
     * Get attendance history for a section.
     */
    public function getHistory(Request $request, Section $section)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $subjectAssignmentId = $request->get('subject_assignment_id');

        $query = Attendance::with(['student', 'recordedBy'])
            ->where('section_id', $section->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc');

        if ($subjectAssignmentId) {
            $query->where('subject_assignment_id', $subjectAssignmentId);
        }

        $attendances = $query->get()->groupBy('date');

        $history = $attendances->map(function ($records, $date) {
            return [
                'date' => $date,
                'total' => $records->count(),
                'present' => $records->where('status', 'present')->count(),
                'absent' => $records->where('status', 'absent')->count(),
                'late' => $records->where('status', 'late')->count(),
                'excused' => $records->where('status', 'excused')->count(),
                'recorded_by' => $records->first()->recordedBy->name ?? 'N/A',
            ];
        })->values();

        return response()->json([
            'history' => $history,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }
}
