<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\RecoveryRegistration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecoveryRegistrationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = RecoveryRegistration::with(['student', 'subject', 'academicPeriod']);

        $user = Auth::user();

        if ($user->hasRole('student')) {
            $query->where('student_id', $user->id);
        }

        if ($user->hasRole('guardian')) {
            $studentIds = $user->students()->pluck('users.id');
            $query->whereIn('student_id', $studentIds);
        }

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->has('academic_period_id')) {
            $query->where('academic_period_id', $request->academic_period_id);
        }

        $registrations = $query->get();

        return $this->sendResponse($registrations, 'Registros de reparación obtenidos exitosamente');
    }

    public function show(int $id): JsonResponse
    {
        $registration = RecoveryRegistration::with(['student', 'subject', 'academicPeriod'])->find($id);

        if (is_null($registration)) {
            return $this->sendError('Registro de reparación no encontrado');
        }

        $this->authorize('view', $registration);

        return $this->sendResponse($registration, 'Registro de reparación obtenido exitosamente');
    }

    public function grade(Request $request, int $id): JsonResponse
    {
        $registration = RecoveryRegistration::find($id);

        if (is_null($registration)) {
            return $this->sendError('Registro de reparación no encontrado');
        }

        $this->authorize('grade', $registration);

        $validated = $request->validate([
            'recovery_score' => 'required|numeric|min:0|max:20',
        ]);

        $status = $validated['recovery_score'] >= 10 ? 'passed' : 'failed';

        $registration->update([
            'recovery_score' => $validated['recovery_score'],
            'status' => $status,
        ]);

        $registration->load(['student', 'subject', 'academicPeriod']);

        return $this->sendResponse($registration, 'Nota de reparación registrada exitosamente');
    }
}
