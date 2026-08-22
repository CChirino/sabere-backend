<?php

namespace App\Http\Controllers\Web\Student;

use App\Http\Controllers\Controller;
use App\Models\Circular;
use App\Models\CircularRecipient;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CircularController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $role = $user->roles->first()?->name ?? 'student';

        $circulars = Circular::published()
            ->forAudience($role)
            ->with('creator')
            ->orderBy('sent_at', 'desc')
            ->paginate(15);

        $readIds = CircularRecipient::where('user_id', $user->id)
            ->whereNotNull('read_at')
            ->pluck('circular_id')
            ->toArray();

        return Inertia::render('Circulars/Index', [
            'circulars' => $circulars,
            'readIds' => $readIds,
        ]);
    }

    public function show(Request $request, int $id)
    {
        $user = $request->user();
        $role = $user->roles->first()?->name ?? 'student';

        $circular = Circular::published()
            ->forAudience($role)
            ->with('creator')
            ->findOrFail($id);

        $recipient = CircularRecipient::firstOrCreate(
            ['circular_id' => $circular->id, 'user_id' => $user->id],
            ['email_sent' => false, 'push_sent' => false]
        );
        $recipient->markAsRead();

        return Inertia::render('Circulars/Show', [
            'circular' => $circular,
        ]);
    }
}
