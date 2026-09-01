<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\Term;
use App\Services\TermClosingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TermClosingController extends Controller
{
    /**
     * Cerrar un lapso y marcar sus notas como finales.
     */
    public function close(Request $request, int $id): JsonResponse
    {
        $term = Term::find($id);

        if (is_null($term)) {
            return $this->sendError('Lapso no encontrado');
        }

        if ($term->is_closed) {
            return $this->sendError('El lapso ya está cerrado', [], 422);
        }

        $this->authorize('close', Term::class);

        $affected = TermClosingService::close($term);

        return $this->sendResponse(
            ['closed_scores' => $affected],
            'Lapso cerrado exitosamente'
        );
    }
}
