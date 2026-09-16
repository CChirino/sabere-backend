<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\IncidentType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncidentTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', IncidentType::class);

        $types = IncidentType::where('is_active', true)
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return $this->sendPaginatedResponse($types, 'Tipos de incidencia obtenidos exitosamente');
    }
}
