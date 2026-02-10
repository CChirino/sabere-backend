<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    /**
     * Listar todos los eventos (filtrados por visibilidad según rol).
     */
    public function index(Request $request): JsonResponse
    {
        $primaryRole = $this->getPrimaryRole(Auth::user());

        $query = Event::with(['creator', 'academicPeriod'])
            ->active()
            ->visibleForRole($primaryRole);

        $this->applyFilters($query, $request);

        $events = $query->orderBy('start_date')->get();

        return $this->sendResponse(
            EventResource::collection($events),
            'Eventos obtenidos exitosamente'
        );
    }

    /**
     * Obtener eventos próximos (para dashboard/notificaciones).
     */
    public function upcoming(Request $request): JsonResponse
    {
        $primaryRole = $this->getPrimaryRole(Auth::user());
        $limit = $request->integer('limit', 5);

        $events = Event::with(['creator'])
            ->active()
            ->visibleForRole($primaryRole)
            ->upcoming()
            ->limit($limit)
            ->get();

        return $this->sendResponse(
            EventResource::collection($events),
            'Eventos próximos obtenidos exitosamente'
        );
    }

    /**
     * Almacenar un nuevo evento.
     */
    public function store(StoreEventRequest $request): JsonResponse
    {
        $event = Event::create($request->validatedWithCreator());

        $event->load(['creator', 'academicPeriod']);

        return $this->sendResponse(
            new EventResource($event),
            'Evento creado exitosamente',
            201
        );
    }

    /**
     * Mostrar un evento específico.
     */
    public function show(Event $event): JsonResponse
    {
        $event->load(['creator', 'academicPeriod']);

        return $this->sendResponse(
            new EventResource($event),
            'Evento obtenido exitosamente'
        );
    }

    /**
     * Actualizar un evento.
     */
    public function update(UpdateEventRequest $request, Event $event): JsonResponse
    {
        $event->update($request->validated());

        $event->load(['creator', 'academicPeriod']);

        return $this->sendResponse(
            new EventResource($event),
            'Evento actualizado exitosamente'
        );
    }

    /**
     * Eliminar un evento.
     */
    public function destroy(Event $event): JsonResponse
    {
        $event->delete();

        return $this->sendResponse(null, 'Evento eliminado exitosamente');
    }

    /**
     * Aplicar filtros a la consulta de eventos.
     */
    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('start') && $request->filled('end')) {
            $query->where(function ($q) use ($request) {
                $q->whereBetween('start_date', [$request->start, $request->end])
                  ->orWhereBetween('end_date', [$request->start, $request->end])
                  ->orWhere(function ($q2) use ($request) {
                      $q2->where('start_date', '<=', $request->start)
                         ->where('end_date', '>=', $request->end);
                  });
            });
        }

        if ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('start_date', $request->month)
                  ->whereYear('start_date', $request->year);
        }

        if ($request->filled('academic_period_id')) {
            $query->where('academic_period_id', $request->academic_period_id);
        }
    }

    /**
     * Obtener el rol principal del usuario.
     */
    private function getPrimaryRole($user): string
    {
        $priority = ['admin', 'director', 'coordinator', 'teacher', 'student', 'guardian'];
        foreach ($priority as $role) {
            if ($user->hasRole($role)) {
                return $role;
            }
        }
        return 'student';
    }
}
