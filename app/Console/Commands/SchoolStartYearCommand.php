<?php

namespace App\Console\Commands;

use App\Models\AcademicPeriod;
use App\Services\SchoolYearService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SchoolStartYearCommand extends Command
{
    protected $signature = 'school:start-year
                            {school_year : Año escolar en formato YYYY-YYYY (ej. 2025-2026)}
                            {--from= : ID del período académico a copiar (default: último activo)}
                            {--start-date= : Fecha de inicio (default: septiembre 16 del primer año)}
                            {--end-date= : Fecha de fin (default: julio 15 del segundo año)}';

    protected $description = 'Crear un nuevo año escolar copiando la estructura del año anterior';

    public function handle(SchoolYearService $service): int
    {
        $schoolYear = $this->argument('school_year');

        // Validar formato
        if (! preg_match('/^\d{4}-\d{4}$/', $schoolYear)) {
            $this->error('El formato del año escolar debe ser YYYY-YYYY (ej. 2025-2026)');

            return self::FAILURE;
        }

        // Validar que no exista
        if (AcademicPeriod::where('code', $schoolYear)->exists()) {
            $this->error("Ya existe un período académico con el código '{$schoolYear}'.");

            return self::FAILURE;
        }

        // Obtener período fuente
        $source = $this->getSourcePeriod();
        if (! $source) {
            $this->error('No se encontró un período académico fuente para copiar.');

            return self::FAILURE;
        }

        // Calcular fechas
        [$startYear, $endYear] = explode('-', $schoolYear);
        $startDate = $this->option('start-date')
            ? Carbon::parse($this->option('start-date'))
            : Carbon::create((int) $startYear, 9, 16);
        $endDate = $this->option('end-date')
            ? Carbon::parse($this->option('end-date'))
            : Carbon::create((int) $endYear, 7, 15);

        // Cargar conteos del período fuente
        $sourceSections = $source->sections()->count();
        $sourceAssignments = $source->subjectAssignments()->count();

        // Mostrar resumen
        $this->info("Nuevo año escolar: {$schoolYear}");
        $this->info("Período fuente: {$source->name} ({$source->code})");
        $this->info("Fechas: {$startDate->format('d/m/Y')} - {$endDate->format('d/m/Y')}");
        $this->info('Se copiarán:');
        $this->line('  - 3 lapsos');
        $this->line("  - {$sourceSections} secciones");
        $this->line("  - {$sourceAssignments} asignaciones profesor-materia");
        $this->line('  - Horarios asociados');
        $this->newLine();

        if (! $this->option('no-interaction') && ! $this->confirm('¿Desea continuar?', true)) {
            $this->info('Operación cancelada.');

            return self::SUCCESS;
        }

        $result = $service->startYear($schoolYear, $source, $startDate, $endDate);

        $this->newLine();
        $this->info('Año escolar creado exitosamente:');
        $this->line("  Período: {$result['period']->name}");
        $this->line("  Lapsos: {$result['terms']}");
        $this->line("  Secciones: {$result['sections']}");
        $this->line("  Asignaciones: {$result['assignments']}");
        $this->line("  Horarios: {$result['schedules']}");

        return self::SUCCESS;
    }

    private function getSourcePeriod(): ?AcademicPeriod
    {
        if ($this->option('from')) {
            return AcademicPeriod::find($this->option('from'));
        }

        return AcademicPeriod::where('status', true)
            ->orderByDesc('id')
            ->first();
    }
}
