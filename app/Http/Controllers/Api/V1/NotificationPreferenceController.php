<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UserNotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $preferences = UserNotificationPreference::preferencesForUser($request->user());

        return response()->json($preferences);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'push_scores' => ['boolean'],
            'push_tasks' => ['boolean'],
            'push_circulars' => ['boolean'],
            'push_events' => ['boolean'],
            'push_reenrollment' => ['boolean'],
            'push_messages' => ['boolean'],
        ]);

        $preferences = UserNotificationPreference::preferencesForUser($request->user());
        $preferences->update($data);

        return response()->json($preferences);
    }
}
