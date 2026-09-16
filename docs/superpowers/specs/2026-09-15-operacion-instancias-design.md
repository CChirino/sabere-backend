# Fase 3.2 — Operación de instancias

## Resumen

Tres comandos artisan para operación de instancias Sabere:
1. **Backups de DB** — volcado mysqldump con compresión, retención y restauración.
2. **Inicio de año escolar** — copia estructura académica del año anterior.
3. **Provisión de nueva instancia** — setup inicial de un colegio nuevo.

---

## 1. Backups de base de datos

### `php artisan db:backup`

- Ejecuta `mysqldump` con credenciales del `.env` (`DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).
- Comprime con gzip.
- Guarda en `storage/app/backups/sabere_YYYY-MM-DD_HHmmss.sql.gz`.
- Registra en activity log: `backup_created`.
- Scheduled: diario a las 02:00 AM.

### `php artisan db:backup-clean`

- Lee setting `backup.retention_days` (default 30).
- Elimina archivos `.sql.gz` en `storage/app/backups/` más viejos que la retención.
- Scheduled: semanal los domingos a las 03:00 AM.

### `php artisan db:backup-restore {filename}`

- Argumento: nombre del archivo en `storage/app/backups/`.
- Opción `--force` para omitir confirmación interactiva.
- Descomprime y ejecuta `mysql` para importar.
- Registra en activity log: `backup_restored`.

### Setting nuevo

| group | key | value | type | is_public |
|---|---|---|---|---|
| backup | retention_days | 30 | integer | false |

---

## 2. Inicio de año escolar

### `php artisan school:start-year {school_year}`

- **Argumento**: `school_year` en formato `YYYY-YYYY` (ej. `2025-2026`).
- **Opción**: `--from={period_id}` — ID del período a copiar (default: último período activo).
- **Opción**: `--start-date={date}` — fecha inicio (default: septiembre 16 del primer año).
- **Opción**: `--end-date={date}` — fecha fin (default: julio 15 del segundo año).

### Flujo

1. Valida formato de `school_year` y que no exista un período con ese código.
2. Carga período fuente con secciones, asignaciones y horarios.
3. Muestra resumen interactivo con conteos antes de ejecutar.
4. En transacción DB:
   a. Crea `AcademicPeriod` con status activo.
   b. Crea 3 `Term` (lapsos) distribuyendo fechas equitativamente con peso 33.33/33.33/33.34.
   c. Copia `Section` del período fuente (mismo `grade_id`, `name`, `capacity`).
   d. Copia `SubjectAssignment` mapeando `section_id` antiguo → nuevo, conservando `teacher_id` y `subject_id`.
   e. Copia `Schedule` mapeando `subject_assignment_id` antiguo → nuevo.

### Service: `SchoolYearService`

```php
class SchoolYearService
{
    public function startYear(string $schoolYear, AcademicPeriod $source, Carbon $startDate, Carbon $endDate): AcademicPeriod;
}
```

Lógica testeable separada del comando.

### No se copia

- Matrículas (enrollments).
- Notas (student_scores, evaluation plans).
- Tareas (tasks, submissions).
- Asistencia (attendances).
- Chat (section_chat_messages).

---

## 3. Provisión de nueva instancia

### `php artisan school:provision`

- **Opción**: `--name={name}` — nombre del plantel.
- **Opción**: `--rif={rif}` — RIF.
- **Opción**: `--code={code}` — código DEA/plantel.
- **Opción**: `--admin-email={email}` — email del admin.
- **Opción**: `--demo` — incluir datos de demostración.
- **Opción**: `--no-interaction` — modo no interactivo (requiere flags).

### Flujo

1. Ejecuta `migrate --force`.
2. Corre seeders base en orden:
   - PermissionSeeder
   - RoleSeeder
   - EducationLevelSeeder
   - GradeSeeder
   - SubjectAreaSeeder
   - SubjectSeeder
   - GradeSubjectSeeder
   - SettingSeeder
3. Solicita interactivamente (o por flags) datos del plantel.
4. Actualiza settings: `institution.name`, `institution.rif`, `institution.dea_code`.
5. Crea usuario admin con rol `admin`.
6. Si `--demo`: ejecuta TermSeeder + DemoSeeder.
7. Genera `APP_KEY` si no existe.

---

## Archivos nuevos

```
app/Console/Commands/DatabaseBackupCommand.php
app/Console/Commands/DatabaseBackupCleanCommand.php
app/Console/Commands/DatabaseBackupRestoreCommand.php
app/Console/Commands/SchoolStartYearCommand.php
app/Console/Commands/SchoolProvisionCommand.php
app/Services/SchoolYearService.php
tests/Feature/Console/SchoolStartYearTest.php
tests/Feature/Console/SchoolProvisionTest.php
```

## Archivos modificados

```
routes/console.php              — agregar schedule de backups
database/seeders/SettingSeeder.php — agregar backup.retention_days
```

---

## Tests

### SchoolStartYearTest

- Crea período fuente con secciones, asignaciones y horarios.
- Ejecuta `school:start-year` con `--no-interaction`.
- Verifica: período nuevo, 3 lapsos, secciones copiadas, asignaciones copiadas, horarios copiados.
- Verifica: no se copiaron matrículas ni notas.
- Verifica: error si el año ya existe.

### SchoolProvisionTest

- Ejecuta `school:provision` con `--no-interaction` y flags.
- Verifica: settings actualizados, usuario admin creado, permisos seeded.

---

## Verificación

```bash
./vendor/bin/pint
php artisan test
npx vue-tsc --noEmit
npm run build
```
