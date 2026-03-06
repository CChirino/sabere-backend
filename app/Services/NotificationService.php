<?php

namespace App\Services;

use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Models\Reenrollment;
use App\Models\StudentScore;
use App\Models\Task;
use App\Models\User;

class NotificationService
{
    /**
     * Notificar a estudiante y representantes sobre una nueva nota.
     */
    public static function notifyScoreAssigned(StudentScore $score): void
    {
        $student = $score->student;
        $subject = $score->subject;
        $teacher = $score->gradedBy;
        $term = $score->term;

        if (!$student) return;

        // Notificar al estudiante
        self::createNotification(
            user: $student,
            type: 'score_assigned',
            title: "Nueva calificación en {$subject->name}",
            message: "Has recibido una calificación de {$score->score} en {$subject->name} - {$term->name}",
            data: [
                'score_id' => $score->id,
                'subject' => $subject->name,
                'score' => $score->score,
                'term' => $term->name,
                'teacher' => $teacher?->name ?? 'Profesor',
            ],
            channel: 'both'
        );

        // Notificar a los representantes
        $guardians = $student->guardians;
        foreach ($guardians as $guardian) {
            self::createNotification(
                user: $guardian,
                type: 'score_assigned',
                title: "Nueva calificación de {$student->name}",
                message: "{$student->name} ha recibido una calificación de {$score->score} en {$subject->name} - {$term->name}",
                data: [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'score_id' => $score->id,
                    'subject' => $subject->name,
                    'score' => $score->score,
                    'term' => $term->name,
                    'teacher' => $teacher?->name ?? 'Profesor',
                ],
                channel: 'both'
            );
        }
    }

    /**
     * Notificar que las notas han sido finalizadas.
     */
    public static function notifyScoresFinalized(int $subjectAssignmentId, int $termId, string $subjectName, string $termName, User $teacher): void
    {
        // Obtener estudiantes calificados en esta asignación/lapso
        $scores = StudentScore::where('subject_assignment_id', $subjectAssignmentId)
            ->where('term_id', $termId)
            ->with('student.guardians')
            ->get();

        $notifiedStudents = [];

        foreach ($scores as $score) {
            $student = $score->student;
            if (!$student || in_array($student->id, $notifiedStudents)) continue;

            $notifiedStudents[] = $student->id;

            // Notificar al estudiante
            self::createNotification(
                user: $student,
                type: 'score_finalized',
                title: "Calificaciones finalizadas en {$subjectName}",
                message: "Las calificaciones de {$subjectName} - {$termName} han sido finalizadas",
                data: [
                    'subject' => $subjectName,
                    'term' => $termName,
                    'teacher' => $teacher->name,
                ],
                channel: 'both'
            );

            // Notificar a representantes
            foreach ($student->guardians as $guardian) {
                self::createNotification(
                    user: $guardian,
                    type: 'score_finalized',
                    title: "Calificaciones finalizadas - {$student->name}",
                    message: "Las calificaciones de {$subjectName} - {$termName} de {$student->name} han sido finalizadas",
                    data: [
                        'student_id' => $student->id,
                        'student_name' => $student->name,
                        'subject' => $subjectName,
                        'term' => $termName,
                        'teacher' => $teacher->name,
                    ],
                    channel: 'both'
                );
            }
        }
    }

    /**
     * Notificar a estudiantes sobre nueva tarea publicada.
     */
    public static function notifyTaskCreated(Task $task): void
    {
        if (!$task->is_published) return;

        $subject = $task->subject;
        $teacher = $task->subjectAssignment?->teacher;

        // Obtener estudiantes de la asignación/sección
        $assignment = $task->subjectAssignment;
        if (!$assignment) return;

        $section = $assignment->section;
        if (!$section) return;

        // Obtener estudiantes inscritos en la sección
        $enrollments = $section->enrollments()->where('status', 'active')->with('student.guardians')->get();

        foreach ($enrollments as $enrollment) {
            $student = $enrollment->student;
            if (!$student) continue;

            // Notificar al estudiante
            self::createNotification(
                user: $student,
                type: 'task_created',
                title: "Nueva tarea: {$task->title}",
                message: "Se ha asignado una nueva tarea en {$subject->name}: {$task->title}",
                data: [
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'subject' => $subject->name,
                    'due_date' => $task->due_date?->format('d/m/Y H:i'),
                    'teacher' => $teacher?->name ?? 'Profesor',
                ],
                channel: 'both'
            );

            // Notificar a representantes
            foreach ($student->guardians as $guardian) {
                self::createNotification(
                    user: $guardian,
                    type: 'task_created',
                    title: "Nueva tarea de {$student->name}",
                    message: "{$student->name} tiene una nueva tarea en {$subject->name}: {$task->title}",
                    data: [
                        'student_id' => $student->id,
                        'student_name' => $student->name,
                        'task_id' => $task->id,
                        'task_title' => $task->title,
                        'subject' => $subject->name,
                        'due_date' => $task->due_date?->format('d/m/Y H:i'),
                        'teacher' => $teacher?->name ?? 'Profesor',
                    ],
                    channel: 'both'
                );
            }
        }
    }

    /**
     * Notificar que una tarea ha sido calificada.
     */
    public static function notifyTaskGraded(Task $task, User $student, float $score, float $maxScore): void
    {
        $subject = $task->subject;

        // Notificar al estudiante
        self::createNotification(
            user: $student,
            type: 'task_graded',
            title: "Tarea calificada: {$task->title}",
            message: "Tu tarea '{$task->title}' ha sido calificada con {$score}/{$maxScore}",
            data: [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'subject' => $subject?->name ?? 'Materia',
                'score' => $score,
                'max_score' => $maxScore,
            ],
            channel: 'both'
        );

        // Notificar a representantes
        foreach ($student->guardians as $guardian) {
            self::createNotification(
                user: $guardian,
                type: 'task_graded',
                title: "Tarea calificada - {$student->name}",
                message: "La tarea '{$task->title}' de {$student->name} ha sido calificada con {$score}/{$maxScore}",
                data: [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'subject' => $subject?->name ?? 'Materia',
                    'score' => $score,
                    'max_score' => $maxScore,
                ],
                channel: 'both'
            );
        }
    }

    /**
     * Notificar que la reinscripción fue aprobada.
     */
    public static function notifyReenrollmentApproved(Reenrollment $reenrollment): void
    {
        $student = $reenrollment->student;
        $academicPeriod = $reenrollment->targetAcademicPeriod;
        $grade = $reenrollment->targetGrade;
        $section = $reenrollment->targetSection;

        if (!$student) return;

        self::createNotification(
            user: $student,
            type: 'reenrollment_approved',
            title: 'Reinscripción Aprobada',
            message: "Tu reinscripción para el período {$academicPeriod?->name} ha sido aprobada",
            data: [
                'reenrollment_id' => $reenrollment->id,
                'academic_period' => $academicPeriod?->name,
                'grade' => $grade?->name,
                'section' => $section?->name,
            ],
            channel: 'both'
        );

        // Notificar a representantes
        foreach ($student->guardians as $guardian) {
            self::createNotification(
                user: $guardian,
                type: 'reenrollment_approved',
                title: "Reinscripción Aprobada - {$student->name}",
                message: "La reinscripción de {$student->name} para el período {$academicPeriod?->name} ha sido aprobada",
                data: [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'reenrollment_id' => $reenrollment->id,
                    'academic_period' => $academicPeriod?->name,
                    'grade' => $grade?->name,
                    'section' => $section?->name,
                ],
                channel: 'both'
            );
        }
    }

    /**
     * Notificar que la reinscripción fue rechazada.
     */
    public static function notifyReenrollmentRejected(Reenrollment $reenrollment): void
    {
        $student = $reenrollment->student;
        $academicPeriod = $reenrollment->targetAcademicPeriod;

        if (!$student) return;

        self::createNotification(
            user: $student,
            type: 'reenrollment_rejected',
            title: 'Reinscripción Rechazada',
            message: "Tu reinscripción para el período {$academicPeriod?->name} ha sido rechazada",
            data: [
                'reenrollment_id' => $reenrollment->id,
                'academic_period' => $academicPeriod?->name,
                'reason' => $reenrollment->admin_notes,
            ],
            channel: 'both'
        );

        // Notificar a representantes
        foreach ($student->guardians as $guardian) {
            self::createNotification(
                user: $guardian,
                type: 'reenrollment_rejected',
                title: "Reinscripción Rechazada - {$student->name}",
                message: "La reinscripción de {$student->name} para el período {$academicPeriod?->name} ha sido rechazada",
                data: [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'reenrollment_id' => $reenrollment->id,
                    'academic_period' => $academicPeriod?->name,
                    'reason' => $reenrollment->admin_notes,
                ],
                channel: 'both'
            );
        }
    }

    /**
     * Notificación masiva para múltiples usuarios.
     */
    public static function notifyBulk(
        User $user,
        string $subject,
        string $message,
        string $priority = 'normal',
        bool $sendEmail = true,
        ?User $sender = null
    ): void {
        $data = [
            'priority' => $priority,
            'sender_name' => $sender?->name ?? 'Administración',
            'sender_email' => $sender?->email,
            'sent_at' => now()->toISOString(),
        ];

        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'bulk_notification',
            'title' => $subject,
            'message' => $message,
            'data' => $data,
            'channel' => $sendEmail ? 'both' : 'database',
            'is_read' => false,
        ]);

        // Enviar email si es necesario
        if ($sendEmail) {
            SendNotificationJob::dispatch($notification);
        }
    }

    /**
     * Crear notificación y enviar email si es necesario.
     */
    private static function createNotification(
        User $user,
        string $type,
        string $title,
        string $message,
        array $data = [],
        string $channel = 'database'
    ): void {
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'channel' => $channel,
        ]);

        // Enviar email si el canal incluye email
        if (in_array($channel, ['email', 'both'])) {
            SendNotificationJob::dispatch($notification);
        }
    }
}
