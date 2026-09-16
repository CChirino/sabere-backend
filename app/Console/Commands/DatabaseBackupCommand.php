<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'db:backup';

    protected $description = 'Crear un backup de la base de datos (mysqldump + gzip)';

    public function handle(): int
    {
        $database = config('database.connections.mysql.database');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port', 3306);
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $filename = 'sabere_'.now()->format('Y-m-d_His').'.sql.gz';
        $directory = storage_path('app/backups');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filepath = "{$directory}/{$filename}";

        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers %s | gzip > %s',
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($filepath)
        );

        $this->info("Creando backup de la base de datos '{$database}'...");

        $result = null;
        $output = null;
        exec($command.' 2>&1', $output, $result);

        if ($result !== 0) {
            $this->error('Error al crear el backup:');
            $this->error(implode("\n", $output));

            return self::FAILURE;
        }

        $size = filesize($filepath);
        $sizeFormatted = $this->formatBytes($size);

        $this->info("Backup creado exitosamente: {$filename} ({$sizeFormatted})");

        activity()
            ->withProperties(['filename' => $filename, 'size' => $size])
            ->log('backup_created');

        return self::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;

        return number_format($bytes / pow(1024, $power), 2).' '.$units[$power];
    }
}
