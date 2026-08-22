<?php

use App\Models\Event;
use App\Services\NotificationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('events:send-reminders', function () {
    $tomorrowStart = now()->addDay()->startOfDay();
    $tomorrowEnd = now()->addDay()->endOfDay();

    $events = Event::where('send_notification', true)
        ->where('status', true)
        ->whereBetween('start_date', [$tomorrowStart, $tomorrowEnd])
        ->get();

    if ($events->isEmpty()) {
        $this->info('No hay eventos para mañana que requieran recordatorio.');

        return;
    }

    $count = 0;
    foreach ($events as $event) {
        NotificationService::notifyEventReminder($event);
        $count++;
        $this->info("Recordatorio enviado para: {$event->title}");
    }

    $this->info("Se enviaron recordatorios para {$count} evento(s).");
})->purpose('Enviar recordatorios por email de eventos que ocurren mañana');

Schedule::command('events:send-reminders')->dailyAt('08:00');
