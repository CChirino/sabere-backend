<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CoordinatorDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoordinatorController extends Controller
{
    public function teachers(Request $request): JsonResponse
    {
        return response()->json(
            app(CoordinatorDataService::class)->teachers($request)
        );
    }

    public function teacherShow(int $id): JsonResponse
    {
        $data = app(CoordinatorDataService::class)->teacherShow($id);
        $code = $data['success'] ? 200 : 404;

        return response()->json($data, $code);
    }

    public function tasksOverview(Request $request): JsonResponse
    {
        return response()->json(
            app(CoordinatorDataService::class)->tasksOverview($request)
        );
    }

    public function scoresOverview(Request $request): JsonResponse
    {
        return response()->json(
            app(CoordinatorDataService::class)->scoresOverview($request)
        );
    }
}
