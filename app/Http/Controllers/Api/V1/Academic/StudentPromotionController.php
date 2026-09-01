<?php

namespace App\Http\Controllers\Api\V1\Academic;

use App\Http\Controllers\Controller;
use App\Models\StudentPromotion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentPromotionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = StudentPromotion::with(['student', 'academicPeriod', 'fromGrade', 'toGrade', 'decidedBy']);

        $user = Auth::user();

        if ($user->hasRole('student')) {
            $query->where('student_id', $user->id);
        }

        if ($user->hasRole('guardian')) {
            $studentIds = $user->students()->pluck('users.id');
            $query->whereIn('student_id', $studentIds);
        }

        if ($request->has('academic_period_id')) {
            $query->where('academic_period_id', $request->academic_period_id);
        }

        $promotions = $query->get();

        return $this->sendResponse($promotions, 'Promociones obtenidas exitosamente');
    }

    public function show(int $id): JsonResponse
    {
        $promotion = StudentPromotion::with(['student', 'academicPeriod', 'fromGrade', 'toGrade', 'decidedBy'])->find($id);

        if (is_null($promotion)) {
            return $this->sendError('Promoción no encontrada');
        }

        $this->authorize('view', $promotion);

        return $this->sendResponse($promotion, 'Promoción obtenida exitosamente');
    }
}
