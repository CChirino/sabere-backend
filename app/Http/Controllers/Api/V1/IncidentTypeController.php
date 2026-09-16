<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\IncidentType;
use Illuminate\Http\JsonResponse;

class IncidentTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', IncidentType::class);

        return response()->json(
            IncidentType::where('is_active', true)
                ->orderBy('name')
                ->get()
        );
    }
}
