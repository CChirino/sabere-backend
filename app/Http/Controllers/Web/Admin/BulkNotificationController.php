<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BulkNotificationController extends Controller
{
    /**
     * Muestra el formulario para enviar notificaciones masivas.
     */
    public function index(): Response
    {
        // Contadores por tipo de destinatario
        $stats = [
            'all_guardians' => User::role('guardian')->count(),
            'all_students' => User::role('student')->count(),
            'all_teachers' => User::role('teacher')->count(),
            'all_users' => User::count(),
        ];

        return Inertia::render('Admin/Notifications/Bulk', [
            'stats' => $stats,
        ]);
    }

    /**
     * Envía notificaciones masivas a los destinatarios seleccionados.
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'recipients' => ['required', 'array'],
            'recipients.*' => ['in:all_guardians,all_students,all_teachers,all_users'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'send_email' => ['boolean'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
        ]);

        $recipients = $this->getRecipients($validated['recipients']);
        $recipientCount = 0;

        // Enviar notificaciones en batch
        foreach ($recipients->chunk(100) as $chunk) {
            foreach ($chunk as $user) {
                NotificationService::notifyBulk(
                    user: $user,
                    subject: $validated['subject'],
                    message: $validated['message'],
                    priority: $validated['priority'],
                    sendEmail: $validated['send_email'] ?? true,
                    sender: auth()->user(),
                );
                $recipientCount++;
            }
        }

        return back()->with('success', "Notificación enviada exitosamente a {$recipientCount} destinatarios.");
    }

    /**
     * Obtiene los usuarios según los filtros de destinatarios.
     *
     * @param array<string> $recipientTypes
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function getRecipients(array $recipientTypes)
    {
        $query = User::query();

        // Si incluye 'all_users', devolvemos todos
        if (in_array('all_users', $recipientTypes)) {
            return $query;
        }

        $query->where(function ($q) use ($recipientTypes) {
            if (in_array('all_guardians', $recipientTypes)) {
                $q->orWhereHas('roles', fn ($role) => $role->where('name', 'guardian'));
            }
            if (in_array('all_students', $recipientTypes)) {
                $q->orWhereHas('roles', fn ($role) => $role->where('name', 'student'));
            }
            if (in_array('all_teachers', $recipientTypes)) {
                $q->orWhereHas('roles', fn ($role) => $role->where('name', 'teacher'));
            }
        });

        return $query;
    }

    /**
     * Muestra el historial de notificaciones masivas enviadas.
     */
    public function history(): Response
    {
        $notifications = DB::table('notifications')
            ->select(
                'type',
                'title',
                DB::raw('COUNT(*) as total_sent'),
                DB::raw('MAX(created_at) as last_sent')
            )
            ->where('type', 'bulk_notification')
            ->groupBy('type', 'title')
            ->orderBy('last_sent', 'desc')
            ->paginate(20);

        return Inertia::render('Admin/Notifications/History', [
            'notifications' => $notifications,
        ]);
    }
}
