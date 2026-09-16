<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DatabaseBackupRestoreCommand extends Command
{
    protected $signature = 'db:backup-restore
                            {filename : Nombre del archivo de backup en storage/app/backups/}
                            {--force : Omitir confirmación interactiva}';

    protected $description = 'Restaurar la base de datos desde un backup';

    public function handle(): int
    {
        $filename = $this->argument('filename');
        $filepath = storage_path("app/backups/{$filename}");

        if (! file_exists($filepath)) {
            $this->error("El archivo de backup no existe: {$filename}");
            $this->line('Backups disponibles:');
            $this->listBackups();

            return self::FAILURE;
        }

        $database = config('database.connections.mysql.database');

        if (! $this->option('force')) {
            $this->warn("ADVERTENCIA: Esto reemplazará TODOS los datos de la base de datos '{$database}'.");
            if (! $this->confirm('¿Está seguro de que desea continuar?')) {
                $this->info('Operación cancelada.');

                return self::SUCCESS;
            }
        }

        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port', 3306);
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $command = sprintf(
            'gunzip -c %s | mysql --host=%s --port=%s --user=%s --password=%s %s',
            escapeshellarg($filepath),
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database)
        );

        $this->info("Restaurando base de datos '{$database}' desde {$filename}...");

        $result = null;
        $output = null;
        exec($command.' 2>&1', $output, $result);

        if ($result !== 0) {
            $this->error('Error al restaurar el backup:');
            $this->error(implode("\n", $output));

            return self::FAILURE;
        }

        $this->info('Base de datos restaurada exitosamente.');

        activity()
            ->withProperties(['filename' => $filename])
            ->log('backup_restored');

        return self::SUCCESS;
    }

    private function listBackups(): void
    {
        $directory = storage_path('app/backups');

        if (! is_dir($directory)) {
            $this->line('  (ninguno)');

            return;
        }

        $files = glob("{$directory}/*.sql.gz");

        if (empty($files)) {
            $this->line('  (ninguno)');

            return;
        }

        rsort($files);
        foreach ($files as $file) {
            $size = number_format(filesize($file) / 1024 / 1024, 2);
            $date = date('Y-m-d H:i:s', filemtime($file));
            $this->line('  '.basename($file)." ({$size} MB, {$date})");
        }
    }
}
