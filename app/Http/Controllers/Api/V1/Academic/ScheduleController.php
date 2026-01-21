<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\SubjectAssignment;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    /**
     * Listar horarios
     */
    public function index(Request $request): JsonResponse
    {
        $query = Schedule::with([
            'subjectAssignment.subject',
            'subjectAssignment.teacher',
            'subjectAssignment.section.grade'
        ]);

        // Filtrar por asignación de materia
        if ($request->has('subject_assignment_id')) {
            $query->where('subject_assignment_id', $request->subject_assignment_id);
        }

        // Filtrar por día
        if ($request->has('day_of_week')) {
            $query->where('day_of_week', $request->day_of_week);
        }

        // Filtrar por sección
        if ($request->has('section_id')) {
            $query->whereHas('subjectAssignment', function ($q) use ($request) {
                $q->where('section_id', $request->section_id);
            });
        }

        // Filtrar por profesor
        if ($request->has('teacher_id')) {
            $query->whereHas('subjectAssignment', function ($q) use ($request) {
                $q->where('teacher_id', $request->teacher_id);
            });
        }

        // Filtrar por período académico
        if ($request->has('academic_period_id')) {
            $query->whereHas('subjectAssignment', function ($q) use ($request) {
                $q->where('academic_period_id', $request->academic_period_id);
            });
        }

        $schedules = $query->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return $this->sendResponse($schedules, 'Horarios obtenidos exitosamente');
    }

    /**
     * Crear un nuevo horario
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subject_assignment_id' => 'required|exists:subject_assignments,id',
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'classroom' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        $assignment = SubjectAssignment::find($request->subject_assignment_id);

        // Verificar conflicto en la sección
        if (Schedule::hasConflict(
            $request->subject_assignment_id,
            $request->day_of_week,
            $request->start_time,
            $request->end_time
        )) {
            return $this->sendError(
                'Ya existe un horario en este día y hora para esta sección',
                [],
                409
            );
        }

        // Verificar conflicto del profesor
        if (Schedule::teacherHasConflict(
            $assignment->teacher_id,
            $request->day_of_week,
            $request->start_time,
            $request->end_time,
            $assignment->academic_period_id
        )) {
            return $this->sendError(
                'El profesor ya tiene asignado otro horario en este día y hora',
                [],
                409
            );
        }

        $schedule = Schedule::create($request->all());
        $schedule->load([
            'subjectAssignment.subject',
            'subjectAssignment.teacher',
            'subjectAssignment.section.grade'
        ]);

        return $this->sendResponse($schedule, 'Horario creado exitosamente', 201);
    }

    /**
     * Mostrar un horario específico
     */
    public function show(int $id): JsonResponse
    {
        $schedule = Schedule::with([
            'subjectAssignment.subject',
            'subjectAssignment.teacher',
            'subjectAssignment.section.grade.educationLevel'
        ])->find($id);

        if (is_null($schedule)) {
            return $this->sendError('Horario no encontrado');
        }

        return $this->sendResponse($schedule, 'Horario obtenido exitosamente');
    }

    /**
     * Actualizar un horario
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $schedule = Schedule::find($id);

        if (is_null($schedule)) {
            return $this->sendError('Horario no encontrado');
        }

        $validator = Validator::make($request->all(), [
            'day_of_week' => 'sometimes|in:monday,tuesday,wednesday,thursday,friday,saturday',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'classroom' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        $dayOfWeek = $request->day_of_week ?? $schedule->day_of_week;
        $startTime = $request->start_time ?? $schedule->start_time->format('H:i');
        $endTime = $request->end_time ?? $schedule->end_time->format('H:i');

        // Verificar conflicto en la sección
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

        // Verificar conflicto del profesor
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

        $schedule->update($request->all());
        $schedule->load([
            'subjectAssignment.subject',
            'subjectAssignment.teacher',
            'subjectAssignment.section.grade'
        ]);

        return $this->sendResponse($schedule, 'Horario actualizado exitosamente');
    }

    /**
     * Eliminar un horario
     */
    public function destroy(int $id): JsonResponse
    {
        $schedule = Schedule::find($id);

        if (is_null($schedule)) {
            return $this->sendError('Horario no encontrado');
        }

        $schedule->delete();

        return $this->sendResponse(null, 'Horario eliminado exitosamente');
    }

    /**
     * Obtener horario semanal de una sección
     */
    public function bySection(int $sectionId): JsonResponse
    {
        $section = Section::find($sectionId);

        if (is_null($section)) {
            return $this->sendError('Sección no encontrada');
        }

        $schedules = Schedule::with(['subjectAssignment.subject', 'subjectAssignment.teacher'])
            ->whereHas('subjectAssignment', function ($q) use ($sectionId) {
                $q->where('section_id', $sectionId)
                  ->where('status', true);
            })
            ->where('status', true)
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        // Ordenar por días de la semana
        $orderedDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        $orderedSchedule = collect($orderedDays)->mapWithKeys(function ($day) use ($schedules) {
            return [$day => $schedules->get($day, collect())];
        });

        $response = [
            'section' => $section->load('grade.educationLevel'),
            'schedule' => $orderedSchedule,
            'days' => Schedule::DAYS,
        ];

        return $this->sendResponse($response, 'Horario de la sección obtenido exitosamente');
    }

    /**
     * Obtener horario semanal de un profesor
     */
    public function byTeacher(int $teacherId): JsonResponse
    {
        $teacher = User::find($teacherId);

        if (is_null($teacher) || !$teacher->hasRole('teacher')) {
            return $this->sendError('Profesor no encontrado');
        }

        $schedules = Schedule::with([
            'subjectAssignment.subject',
            'subjectAssignment.section.grade.educationLevel'
        ])
            ->whereHas('subjectAssignment', function ($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId)
                  ->where('status', true);
            })
            ->where('status', true)
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        // Ordenar por días de la semana
        $orderedDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        $orderedSchedule = collect($orderedDays)->mapWithKeys(function ($day) use ($schedules) {
            return [$day => $schedules->get($day, collect())];
        });

        $response = [
            'teacher' => $teacher,
            'schedule' => $orderedSchedule,
            'days' => Schedule::DAYS,
        ];

        return $this->sendResponse($response, 'Horario del profesor obtenido exitosamente');
    }

    /**
     * Obtener horario semanal de un estudiante (basado en su sección)
     */
    public function byStudent(int $studentId): JsonResponse
    {
        $student = User::find($studentId);

        if (is_null($student) || !$student->hasRole('student')) {
            return $this->sendError('Estudiante no encontrado');
        }

        $enrollment = $student->activeEnrollment();

        if (is_null($enrollment)) {
            return $this->sendError('El estudiante no tiene una inscripción activa');
        }

        return $this->bySection($enrollment->section_id);
    }

    /**
     * Obtener horario del día actual para una sección
     */
    public function todayBySection(int $sectionId): JsonResponse
    {
        $section = Section::find($sectionId);

        if (is_null($section)) {
            return $this->sendError('Sección no encontrada');
        }

        $today = strtolower(now()->format('l')); // monday, tuesday, etc.

        $schedules = Schedule::with(['subjectAssignment.subject', 'subjectAssignment.teacher'])
            ->whereHas('subjectAssignment', function ($q) use ($sectionId) {
                $q->where('section_id', $sectionId)
                  ->where('status', true);
            })
            ->where('day_of_week', $today)
            ->where('status', true)
            ->orderBy('start_time')
            ->get();

        $response = [
            'section' => $section->load('grade.educationLevel'),
            'day' => $today,
            'day_name' => Schedule::DAYS[$today] ?? $today,
            'schedules' => $schedules,
        ];

        return $this->sendResponse($response, 'Horario del día obtenido exitosamente');
    }
}
