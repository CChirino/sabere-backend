<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DatabaseBackupCleanCommand extends Command
{
    protected $signature = 'db:backup-clean';

    protected $description = 'Eliminar backups de base de datos más viejos que la retención configurada';

    public function handle(): int
    {
        $retentionDays = Setting::get('backup.retention_days', 30);
        $directory = storage_path('app/backups');

        if (! is_dir($directory)) {
            $this->info('No hay directorio de backups.');

            return self::SUCCESS;
        }

        $cutoff = Carbon::now()->subDays($retentionDays);
        $files = glob("{$directory}/*.sql.gz");
        $deleted = 0;

        foreach ($files as $file) {
            $fileTime = Carbon::createFromTimestamp(filemtime($file));

            if ($fileTime->isBefore($cutoff)) {
                unlink($file);
                $deleted++;
                $this->line('Eliminado: '.basename($file));
            }
        }

        $this->info("Limpieza completada. {$deleted} backup(s) eliminado(s). Retención: {$retentionDays} días.");

        if ($deleted > 0) {
            activity()
                ->withProperties(['deleted_count' => $deleted, 'retention_days' => $retentionDays])
                ->log('backup_cleaned');
        }

        return self::SUCCESS;
    }
}
