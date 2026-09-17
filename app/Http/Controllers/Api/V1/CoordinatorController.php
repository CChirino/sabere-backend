<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CoordinatorDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoordinatorController extends Controller
{
    public function teachers(Request $request): JsonResponse
    {
        $teachers = app(CoordinatorDataService::class)->teachers($request);

        return $this->sendPaginatedResponse($teachers, 'Profesores obtenidos exitosamente');
    }

    public function teacherShow(int $id): JsonResponse
    {
        $teacher = app(CoordinatorDataService::class)->teacherShow($id);

        if (! $teacher) {
            return $this->sendError('Profesor no encontrado');
        }

        return $this->sendResponse($teacher, 'Profesor obtenido exitosamente');
    }

    public function tasksOverview(Request $request): JsonResponse
    {
        $data = app(CoordinatorDataService::class)->tasksOverview($request);

        return $this->sendResponse($data, 'Resumen de tareas obtenido exitosamente');
    }

    public function scoresOverview(Request $request): JsonResponse
    {
        $data = app(CoordinatorDataService::class)->scoresOverview($request);

        return $this->sendResponse($data, 'Resumen de notas obtenido exitosamente');
    }
}
