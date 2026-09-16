# Fase 6 — Entrega 1: Auditoría N+1 e índices

## Resumen

Identificar y corregir consultas N+1 en los endpoints más usados, activar `Model::preventLazyLoading()` en desarrollo y tests, agregar índices faltantes en tablas de alto volumen y añadir eager loading donde sea necesario.

---

## Alcance

### Dentro del alcance

- Activar `Model::preventLazyLoading()` solo en `local`/`testing` (no en producción).
- Correr la suite de tests para detectar N+1.
- Revisar los controladores API con listados más frecuentes:
  - `DisciplinaryRecordController::index`
  - `JustificationController::index`
  - `StudentProfileController`
  - `AdmissionController::index`
  - `DashboardMetricService` (asistencia, notas, riesgo)
  - `DirectMessageController::index`
- Agregar `with()` eager loading y `select` mínimos cuando haga sentido.
- Agregar índices compuestos en migraciones nuevas:
  - `attendances`: `(student_id, academic_period_id, date)`
  - `student_scores`: `(subject_assignment_id, student_id, term_id)`
  - `disciplinary_records`: `(academic_period_id, severity, student_id)`
  - `justifications`: `(academic_period_id, status, student_id)`
- Actualizar tests para asegurar que no se introduzcan N+1 en endpoints críticos.

### Fuera del alcance

- Refactor completo de controladores a Actions/Services.
- Cambiar cache/queue a Redis.
- Paginación global en todos los endpoints (entrega 2).

---

## 1. Configuración de desarrollo

En `AppServiceProvider::boot()` (o `EventServiceProvider` alternativa según convención del repo):

```php
if (! app()->isProduction()) {
    \Illuminate\Database\Eloquent\Model::preventLazyLoading(! app()->runningUnitTests());
}
```

Nota: en tests se deja permitido para no romper tests existentes o se corrigen todos. Se propone activar y corregir.

---

## 2. Índices

Agregar migraciones separadas para no romper orden existente:

- `2026_09_15_130000_add_indexes_to_attendances_table.php`
- `2026_09_15_130100_add_indexes_to_student_scores_table.php`
- `2026_09_15_130200_add_indexes_to_disciplinary_records_table.php`
- `2026_09_15_130300_add_indexes_to_justifications_table.php`

Cada una agrega el índice compuesto correspondiente.

---

## 3. Correcciones de eager loading

Revisar y corregir:

- `DisciplinaryRecordController::index` ya carga `student`, `incidentType`, `recordedBy`. Verificar `section` y `academicPeriod`.
- `JustificationController::index` ya carga `student`, `guardian`, `academicPeriod`, `reviewedBy`. Verificar paginación.
- `AdmissionController::index` cargar `academicPeriod`, `grade`, `createdBy`, `processedBy`.
- `DashboardMetricService`: en `atRiskStudents` usar eager loading de relaciones.
- `DirectMessageController::index` cargar `sender`, `recipient`.

---

## 4. Verificación

```bash
./vendor/bin/pint
php artisan test
npx vue-tsc --noEmit
npm run build
```
