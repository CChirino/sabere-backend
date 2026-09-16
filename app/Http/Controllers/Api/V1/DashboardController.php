<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardDataService;
use App\Services\DashboardMetricService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Dashboard principal - redirige según el rol
     */
    public function index(): JsonResponse
    {
        $data = app(DashboardDataService::class)->forUser(Auth::user());

        return $this->sendResponse($data, 'Dashboard obtenido');
    }

    /**
     * Indicadores operativos del período académico.
     */
    public function indicators(Request $request): JsonResponse
    {
        $academicPeriodId = $request->input('academic_period_id');
        $data = app(DashboardMetricService::class)->all($academicPeriodId ? (int) $academicPeriodId : null);

        return $this->sendResponse($data, 'Indicadores del dashboard');
    }
}
