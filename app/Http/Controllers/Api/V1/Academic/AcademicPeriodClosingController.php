<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Services\AcademicPeriodClosingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AcademicPeriodClosingController extends Controller
{
    /**
     * Cerrar un período académico y calcular notas anuales + promociones.
     */
    public function close(Request $request, int $id): JsonResponse
    {
        $period = AcademicPeriod::find($id);

        if (is_null($period)) {
            return $this->sendError('Período académico no encontrado');
        }

        $this->authorize('close', AcademicPeriod::class);

        $validated = $request->validate([
            'to_grade_id' => 'nullable|exists:grades,id',
        ]);

        $results = AcademicPeriodClosingService::close(
            $period,
            Auth::id(),
            $validated['to_grade_id'] ?? null
        );

        return $this->sendResponse($results, 'Período académico cerrado exitosamente');
    }
}
