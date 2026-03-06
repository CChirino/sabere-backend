<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'channel',
        'read_at',
        'sent_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Marcar la notificación como leída.
     */
    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Marcar la notificación como enviada.
     */
    public function markAsSent(): void
    {
        if (!$this->sent_at) {
            $this->update(['sent_at' => now()]);
        }
    }

    /**
     * Verificar si la notificación ha sido leída.
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Scope para notificaciones no leídas.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope para notificaciones recientes.
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Tipos de notificación disponibles.
     */
    public static function getTypes(): array
    {
        return [
            'score_assigned' => 'Nota Asignada',
            'score_finalized' => 'Notas Finalizadas',
            'task_created' => 'Nueva Tarea',
            'task_due_reminder' => 'Recordatorio de Tarea',
            'task_submitted' => 'Tarea Entregada',
            'task_graded' => 'Tarea Calificada',
            'reenrollment_approved' => 'Reinscripción Aprobada',
            'reenrollment_rejected' => 'Reinscripción Rechazada',
            'event_reminder' => 'Recordatorio de Evento',
        ];
    }

    /**
     * Obtener el nombre legible del tipo.
     */
    public function getTypeNameAttribute(): string
    {
        return self::getTypes()[$this->type] ?? $this->type;
    }
}
