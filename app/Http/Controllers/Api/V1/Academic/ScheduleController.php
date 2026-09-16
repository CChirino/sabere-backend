<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\User;
use App\Services\ScheduleDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $schedules = app(ScheduleDataService::class)->index($request);

        return $this->sendPaginatedResponse($schedules, 'Horarios obtenidos exitosamente');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject_assignment_id' => 'required|exists:subject_assignments,id',
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'classroom' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'status' => 'boolean',
        ]);

        $this->authorize('create', Schedule::class);

        $assignment = \App\Models\SubjectAssignment::findOrFail($validated['subject_assignment_id']);

        if (Schedule::hasConflict(
            $validated['subject_assignment_id'],
            $validated['day_of_week'],
            $validated['start_time'],
            $validated['end_time']
        )) {
            return $this->sendError(
                'Ya existe un horario en este día y hora para esta sección',
                [],
                409
            );
        }

        if (Schedule::teacherHasConflict(
            $assignment->teacher_id,
            $validated['day_of_week'],
            $validated['start_time'],
            $validated['end_time'],
            $assignment->academic_period_id
        )) {
            return $this->sendError(
                'El profesor ya tiene asignado otro horario en este día y hora',
                [],
                409
            );
        }

        $schedule = Schedule::create([
            'subject_assignment_id' => $validated['subject_assignment_id'],
            'day_of_week' => $validated['day_of_week'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'classroom' => $validated['classroom'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => $validated['status'] ?? true,
        ]);
        $schedule->load([
            'subjectAssignment.subject',
            'subjectAssignment.teacher',
            'subjectAssignment.section.grade',
        ]);

        return $this->sendResponse($schedule, 'Horario creado exitosamente', 201);
    }

    public function show(int $id): JsonResponse
    {
        $schedule = Schedule::with([
            'subjectAssignment.subject',
            'subjectAssignment.teacher',
            'subjectAssignment.section.grade.educationLevel',
        ])->find($id);

        if (is_null($schedule)) {
            return $this->sendError('Horario no encontrado');
        }

        $this->authorize('view', $schedule);

        return $this->sendResponse($schedule, 'Horario obtenido exitosamente');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $schedule = Schedule::find($id);

        if (is_null($schedule)) {
            return $this->sendError('Horario no encontrado');
        }

        $this->authorize('update', $schedule);

        $validated = $request->validate([
            'day_of_week' => 'sometimes|in:monday,tuesday,wednesday,thursday,friday,saturday',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'classroom' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'status' => 'boolean',
        ]);

        $dayOfWeek = $validated['day_of_week'] ?? $schedule->day_of_week;
        $startTime = $validated['start_time'] ?? $schedule->start_time->format('H:i');
        $endTime = $validated['end_time'] ?? $schedule->end_time->format('H:i');

        if (Schedule::hasConflict(
            $schedule->subject_assignment_id,
            $dayOfWeek,
            $startTime,
            $endTime,
            $id
        )) {
            return $this->sendError(
                'Ya existe un horario en este día y hora para esta sección',
                [],
                409
            );
        }

        $assignment = $schedule->subjectAssignment;
        if (Schedule::teacherHasConflict(
            $assignment->teacher_id,
            $dayOfWeek,
            $startTime,
            $endTime,
            $assignment->academic_period_id,
            $id
        )) {
            return $this->sendError(
                'El profesor ya tiene asignado otro horario en este día y hora',
                [],
                409
            );
        }

        $schedule->update($validated);
        $schedule->load([
            'subjectAssignment.subject',
            'subjectAssignment.teacher',
            'subjectAssignment.section.grade',
        ]);

        return $this->sendResponse($schedule, 'Horario actualizado exitosamente');
    }

    public function destroy(int $id): JsonResponse
    {
        $schedule = Schedule::find($id);

        if (is_null($schedule)) {
            return $this->sendError('Horario no encontrado');
        }

        $this->authorize('delete', $schedule);

        $schedule->delete();

        return $this->sendResponse(null, 'Horario eliminado exitosamente');
    }

    public function bySection(int $sectionId): JsonResponse
    {
        $this->authorize('viewBySection', [Schedule::class, $sectionId]);

        $section = Section::find($sectionId);

        if (is_null($section)) {
            return $this->sendError('Sección no encontrada');
        }

        $data = app(ScheduleDataService::class)->weeklyForSection($section);

        return $this->sendResponse($data, 'Horario de la sección obtenido exitosamente');
    }

    public function byTeacher(int $teacherId): JsonResponse
    {
        $this->authorize('viewByTeacher', [Schedule::class, $teacherId]);

        $teacher = User::find($teacherId);

        if (is_null($teacher) || ! $teacher->hasRole('teacher')) {
            return $this->sendError('Profesor no encontrado');
        }

        $data = app(ScheduleDataService::class)->weeklyForTeacher($teacher);

        return $this->sendResponse($data, 'Horario del profesor obtenido exitosamente');
    }

    public function byStudent(int $studentId): JsonResponse
    {
        $this->authorize('viewByStudent', [Schedule::class, $studentId]);

        $student = User::find($studentId);

        if (is_null($student) || ! $student->hasRole('student')) {
            return $this->sendError('Estudiante no encontrado');
        }

        $enrollment = $student->activeEnrollment();

        if (is_null($enrollment)) {
            return $this->sendError('El estudiante no tiene una inscripción activa');
        }

        return $this->bySection($enrollment->section_id);
    }

    public function todayBySection(int $sectionId): JsonResponse
    {
        $this->authorize('viewBySection', [Schedule::class, $sectionId]);

        $section = Section::find($sectionId);

        if (is_null($section)) {
            return $this->sendError('Sección no encontrada');
        }

        $today = strtolower(now()->format('l'));
        $data = app(ScheduleDataService::class)->todayForSection($section, $today);

        return $this->sendResponse($data, 'Horario del día obtenido exitosamente');
    }
}
