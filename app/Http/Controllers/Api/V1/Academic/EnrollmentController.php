<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Services\EnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = app(EnrollmentService::class)->queryForUser($request);
        $enrollments = $query->orderBy('enrollment_date', 'desc')->paginate($this->perPage($request));

        return $this->sendPaginatedResponse($enrollments, 'Inscripciones obtenidas exitosamente');
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Enrollment::class);

        try {
            $enrollment = app(EnrollmentService::class)->create($request);
        } catch (\InvalidArgumentException $e) {
            $message = $e->getMessage();
            $code = str_contains($message, 'capacidad máxima') ? 409 : 422;

            return $this->sendError($message, [], $code);
        }

        return $this->sendResponse($enrollment, 'Inscripción creada exitosamente', 201);
    }

    public function show(int $id): JsonResponse
    {
        $enrollment = Enrollment::with(['student', 'section.grade.educationLevel', 'academicPeriod'])
            ->find($id);

        if (is_null($enrollment)) {
            return $this->sendError('Inscripción no encontrada');
        }

        $this->authorize('view', $enrollment);

        return $this->sendResponse($enrollment, 'Inscripción obtenida exitosamente');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $enrollment = Enrollment::find($id);

        if (is_null($enrollment)) {
            return $this->sendError('Inscripción no encontrada');
        }

        $this->authorize('update', $enrollment);

        try {
            $enrollment = app(EnrollmentService::class)->update($enrollment, $request);
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), [], 409);
        }

        return $this->sendResponse($enrollment, 'Inscripción actualizada exitosamente');
    }

    public function destroy(int $id): JsonResponse
    {
        $enrollment = Enrollment::find($id);

        if (is_null($enrollment)) {
            return $this->sendError('Inscripción no encontrada');
        }

        $this->authorize('delete', $enrollment);

        $enrollment->delete();

        return $this->sendResponse(null, 'Inscripción eliminada exitosamente');
    }

    public function byStudent(int $studentId): JsonResponse
    {
        $this->authorize('viewByStudent', [Enrollment::class, $studentId]);

        try {
            $enrollments = app(EnrollmentService::class)->byStudent($studentId);
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), [], 404);
        }

        return $this->sendResponse($enrollments, 'Inscripciones del estudiante obtenidas exitosamente');
    }

    public function transfer(Request $request, int $id): JsonResponse
    {
        $enrollment = Enrollment::find($id);

        if (is_null($enrollment)) {
            return $this->sendError('Inscripción no encontrada');
        }

        $this->authorize('transfer', $enrollment);

        try {
            $enrollment = app(EnrollmentService::class)->transfer($enrollment, $request);
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), [], 409);
        }

        return $this->sendResponse($enrollment, 'Estudiante transferido exitosamente');
    }
}
