<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Services\SectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = app(SectionService::class)->queryForUser($request);
        $sections = $query->paginate($this->perPage($request));

        return $this->sendPaginatedResponse($sections, 'Secciones obtenidas exitosamente');
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Section::class);

        try {
            $section = app(SectionService::class)->create($request);
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), [], 409);
        }

        return $this->sendResponse($section, 'Sección creada exitosamente', 201);
    }

    public function show(int $id): JsonResponse
    {
        $section = Section::with(['grade.educationLevel', 'academicPeriod', 'enrollments.student'])
            ->find($id);

        if (is_null($section)) {
            return $this->sendError('Sección no encontrada');
        }

        $this->authorize('view', $section);

        return $this->sendResponse($section, 'Sección obtenida exitosamente');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $section = Section::find($id);

        if (is_null($section)) {
            return $this->sendError('Sección no encontrada');
        }

        $this->authorize('update', $section);

        try {
            $section = app(SectionService::class)->update($section, $request);
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), [], 409);
        }

        return $this->sendResponse($section, 'Sección actualizada exitosamente');
    }

    public function destroy(int $id): JsonResponse
    {
        $section = Section::find($id);

        if (is_null($section)) {
            return $this->sendError('Sección no encontrada');
        }

        $this->authorize('delete', $section);

        if (! app(SectionService::class)->canDestroy($section)) {
            return $this->sendError(
                'No se puede eliminar la sección porque tiene estudiantes inscritos',
                [],
                409
            );
        }

        $section->delete();

        return $this->sendResponse(null, 'Sección eliminada exitosamente');
    }

    public function students(int $id): JsonResponse
    {
        $section = Section::find($id);

        if (is_null($section)) {
            return $this->sendError('Sección no encontrada');
        }

        $this->authorize('viewStudents', $section);

        return $this->sendResponse(
            app(SectionService::class)->students($section),
            'Estudiantes de la sección obtenidos exitosamente'
        );
    }

    public function subjects(int $id): JsonResponse
    {
        $section = Section::find($id);

        if (is_null($section)) {
            return $this->sendError('Sección no encontrada');
        }

        $this->authorize('viewSubjects', $section);

        return $this->sendResponse(
            app(SectionService::class)->subjects($section),
            'Materias de la sección obtenidas exitosamente'
        );
    }
}
