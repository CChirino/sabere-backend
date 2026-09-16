# Fase 6 — Entrega 3: Refactor de controladores grandes

## Resumen

Reducir el tamaño y la complejidad ciclomática de los controladores API más grandes extrayendo lógica a servicios de consulta y acciones de dominio, sin cambiar contratos de respuesta ni permisos.

---

## Alcance

### Dentro del alcance

- Controladores API con más de 200 líneas de código (LOC) en `app/Http/Controllers/Api/V1/`:
  - `DashboardController.php` (428 LOC) — extraer cálculo de métricas a `DashboardMetricService`.
  - `ScheduleController.php` (399 LOC) — extraer query builder de horarios a `ScheduleQueryService`.
  - `EvaluationPlanController.php` (346 LOC) — extraer lógica de aprobación/cierre a `EvaluationPlanApprovalService`.
  - `StudentScoreController.php` (318 LOC) — extraer generación de reportes a `ReportCardService`.
  - `CoordinatorController.php` (293 LOC) — extraer agregaciones de profesores/tareas/asignaciones a `CoordinatorDashboardService`.
  - `EnrollmentController.php` (274 LOC) — extraer filtrado y transferencia a `EnrollmentQueryService`.
  - `StudentGuardianController.php` (267 LOC) — extraer relaciones a `StudentGuardianService`.
  - `TaskController.php` / `TaskSubmissionController.php` (264/262 LOC) — extraer query scopes a `TaskQueryService`.
  - `SubjectController.php` / `SubjectAssignmentController.php` / `SectionController.php` (~260/230 LOC) — extraer filtros por rol a `AcademicAccessQuery`.
  - `StudentEvaluationScoreController.php` (228 LOC) — extraer cálculo de notas a `StudentEvaluationScoreService`.
  - `ManualScoreController.php` (201 LOC) — extraer scoring a `ManualScoreService`.

### Fuera del alcance

- No cambiar rutas, respuestas JSON, permisos ni políticas.
- No mover controllers a otra carpeta.
- No agregar librerías externas.
- No refactorizar controladores Web/Inertia en esta entrega.

---

## Principios

1. **Mantener contrato de respuesta**: los controllers deben seguir devolviendo la misma estructura JSON.
2. **Un controlador, una responsabilidad**: HTTP (autorización, validación, respuesta). Lógica de dominio va a Services.
3. **Services de consulta (Query)**: construyen el `Builder` con filtros y scopes, permiten `paginate()`.
4. **Actions de escritura**: encapsulan una operación compleja (aprobar plan, transferir estudiante, calificar).
5. **Reutilizar FormRequests**: validación `store` / `update` se centraliza cuando no exista.
6. **Tests**: agregar 1–2 tests por service extraído para cubrir casos positivos y permisos.

---

## Patrón de refactor

Ejemplo para `ScheduleController::index`:

```php
public function index(Request $request): JsonResponse
{
    $this->authorize('viewAny', Schedule::class);

    $query = app(ScheduleQueryService::class)
        ->forUser($request->user())
        ->withFilters($request);

    $schedules = $query->orderBy('day_of_week')
        ->orderBy('start_time')
        ->paginate($this->perPage($request));

    return $this->sendPaginatedResponse($schedules, 'Horarios obtenidos exitosamente');
}
```

Ejemplo para `EvaluationPlanController::approve`:

```php
public function approve(Request $request, EvaluationPlan $plan): JsonResponse
{
    $this->authorize('approve', $plan);

    $plan = app(EvaluationPlanApprovalService::class)->approve($plan, $request->user());

    return $this->sendResponse($plan, 'Plan aprobado exitosamente');
}
```

---

## Servicios a crear

| Service | Responsabilidad | Controladores afectados |
|---|---|---|
| `DashboardMetricService` (ya existe, expandir) | Cálculo de indicadores. | `DashboardController` |
| `ScheduleQueryService` | Query de horarios filtrado por rol/día/sección. | `ScheduleController` |
| `EvaluationPlanApprovalService` | Aprobación/cierre de planes y validaciones. | `EvaluationPlanController` |
| `ReportCardService` | Boletines y promedios. | `StudentScoreController` |
| `CoordinatorDashboardService` | Agregaciones para la vista del coordinador. | `CoordinatorController` |
| `EnrollmentQueryService` | Búsqueda de inscripciones y transferencia. | `EnrollmentController` |
| `StudentGuardianService` | CRUD de relaciones y consultas. | `StudentGuardianController` |
| `TaskQueryService` | Tareas filtradas por rol/sección/estado. | `TaskController`, `TaskSubmissionController` |
| `AcademicAccessQuery` | Filtros de acceso académico por rol. | `SubjectController`, `SubjectAssignmentController`, `SectionController` |
| `StudentEvaluationScoreService` | Cálculo y consulta de notas por ítem. | `StudentEvaluationScoreController` |
| `ManualScoreService` | Lógica de notas manuales. | `ManualScoreController` |

---

## Verificación

```bash
./vendor/bin/pint
php artisan test
npx vue-tsc --noEmit
npm run build
```

---

## Orden de implementación recomendado

1. `DashboardController` (más grande, impacto visible).
2. `CoordinatorController` (mucha lógica de agregación).
3. `ScheduleController` (query compleja).
4. `EvaluationPlanController` (lógica de aprobación).
5. Resto por prioridad / tamaño.
