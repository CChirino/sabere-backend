<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\DocumentVerification;
use App\Models\User;
use App\Services\DocumentGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    /**
     * Boletín de calificaciones por lapso.
     */
    public function reportCard(int $studentId, int $termId): Response
    {
        $student = User::find($studentId);

        if (is_null($student) || ! $student->hasRole('student')) {
            return response('Estudiante no encontrado', 404);
        }

        $this->authorize('viewDocuments', [$student]);

        $result = DocumentGenerator::reportCard($studentId, $termId, Auth::id());

        return $result['pdf']->download($result['filename']);
    }

    /**
     * Constancia de estudio.
     */
    public function studyCertificate(int $studentId, Request $request): Response
    {
        $student = User::find($studentId);

        if (is_null($student) || ! $student->hasRole('student')) {
            return response('Estudiante no encontrado', 404);
        }

        $this->authorize('viewDocuments', [$student]);

        $validated = $request->validate([
            'academic_period_id' => 'required|exists:academic_periods,id',
        ]);

        $result = DocumentGenerator::studyCertificate(
            $studentId,
            $validated['academic_period_id'],
            Auth::id()
        );

        return $result['pdf']->download($result['filename']);
    }

    /**
     * Verificar autenticidad de un documento por hash.
     */
    public function verify(string $hash): JsonResponse
    {
        $document = DocumentVerification::where('hash', $hash)->first();

        if (is_null($document) || $document->isExpired()) {
            return $this->sendError('Documento no encontrado o vencido', [], 404);
        }

        return $this->sendResponse([
            'document_type' => $document->document_type,
            'document_id' => $document->document_id,
            'generated_at' => $document->created_at,
            'expires_at' => $document->expires_at,
            'metadata' => $document->metadata,
            'is_valid' => true,
        ], 'Documento verificado exitosamente');
    }
}
