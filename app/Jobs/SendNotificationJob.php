<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Notification $notification;

    /**
     * Create a new job instance.
     */
    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = $this->notification->user;

        if (!$user || !$user->email) {
            return;
        }

        // Enviar email según el tipo de notificación
        match ($this->notification->type) {
            'score_assigned' => $this->sendScoreAssignedEmail($user),
            'score_finalized' => $this->sendScoreFinalizedEmail($user),
            'task_created' => $this->sendTaskCreatedEmail($user),
            'task_due_reminder' => $this->sendTaskDueReminderEmail($user),
            'task_graded' => $this->sendTaskGradedEmail($user),
            'reenrollment_approved' => $this->sendReenrollmentApprovedEmail($user),
            'reenrollment_rejected' => $this->sendReenrollmentRejectedEmail($user),
            default => null,
        };

        $this->notification->markAsSent();
    }

    /**
     * Enviar email de nota asignada.
     */
    private function sendScoreAssignedEmail(User $user): void
    {
        $data = $this->notification->data;

        Mail::send('emails.notifications.score-assigned', [
            'user' => $user,
            'subject' => $data['subject'] ?? 'Materia',
            'score' => $data['score'] ?? 0,
            'term' => $data['term'] ?? '',
            'teacher' => $data['teacher'] ?? '',
        ], function ($message) use ($user) {
            $message->to($user->email, $user->name)
                ->subject('Nueva calificación asignada - ' . config('app.name'));
        });
    }

    /**
     * Enviar email de notas finalizadas.
     */
    private function sendScoreFinalizedEmail(User $user): void
    {
        $data = $this->notification->data;

        Mail::send('emails.notifications.score-finalized', [
            'user' => $user,
            'subject' => $data['subject'] ?? 'Materia',
            'term' => $data['term'] ?? '',
            'teacher' => $data['teacher'] ?? '',
        ], function ($message) use ($user) {
            $message->to($user->email, $user->name)
                ->subject('Calificaciones finalizadas - ' . config('app.name'));
        });
    }

    /**
     * Enviar email de nueva tarea.
     */
    private function sendTaskCreatedEmail(User $user): void
    {
        $data = $this->notification->data;

        Mail::send('emails.notifications.task-created', [
            'user' => $user,
            'taskTitle' => $data['task_title'] ?? 'Tarea',
            'subject' => $data['subject'] ?? 'Materia',
            'dueDate' => $data['due_date'] ?? null,
            'teacher' => $data['teacher'] ?? '',
        ], function ($message) use ($user) {
            $message->to($user->email, $user->name)
                ->subject('Nueva tarea asignada - ' . config('app.name'));
        });
    }

    /**
     * Enviar email de recordatorio de tarea.
     */
    private function sendTaskDueReminderEmail(User $user): void
    {
        $data = $this->notification->data;

        Mail::send('emails.notifications.task-due-reminder', [
            'user' => $user,
            'taskTitle' => $data['task_title'] ?? 'Tarea',
            'subject' => $data['subject'] ?? 'Materia',
            'dueDate' => $data['due_date'] ?? null,
        ], function ($message) use ($user) {
            $message->to($user->email, $user->name)
                ->subject('Recordatorio: Tarea por vencer - ' . config('app.name'));
        });
    }

    /**
     * Enviar email de tarea calificada.
     */
    private function sendTaskGradedEmail(User $user): void
    {
        $data = $this->notification->data;

        Mail::send('emails.notifications.task-graded', [
            'user' => $user,
            'taskTitle' => $data['task_title'] ?? 'Tarea',
            'subject' => $data['subject'] ?? 'Materia',
            'score' => $data['score'] ?? 0,
            'maxScore' => $data['max_score'] ?? 100,
        ], function ($message) use ($user) {
            $message->to($user->email, $user->name)
                ->subject('Tarea calificada - ' . config('app.name'));
        });
    }

    /**
     * Enviar email de reinscripción aprobada.
     */
    private function sendReenrollmentApprovedEmail(User $user): void
    {
        $data = $this->notification->data;

        Mail::send('emails.notifications.reenrollment-approved', [
            'user' => $user,
            'academicPeriod' => $data['academic_period'] ?? '',
            'grade' => $data['grade'] ?? '',
            'section' => $data['section'] ?? '',
        ], function ($message) use ($user) {
            $message->to($user->email, $user->name)
                ->subject('Reinscripción aprobada - ' . config('app.name'));
        });
    }

    /**
     * Enviar email de reinscripción rechazada.
     */
    private function sendReenrollmentRejectedEmail(User $user): void
    {
        $data = $this->notification->data;

        Mail::send('emails.notifications.reenrollment-rejected', [
            'user' => $user,
            'academicPeriod' => $data['academic_period'] ?? '',
            'reason' => $data['reason'] ?? '',
        ], function ($message) use ($user) {
            $message->to($user->email, $user->name)
                ->subject('Reinscripción rechazada - ' . config('app.name'));
        });
    }
}
