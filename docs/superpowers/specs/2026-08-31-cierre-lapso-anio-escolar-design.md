# Diseño — Cierre de lapso y año escolar

## Contexto

Ya existe el plan de evaluación por lapso (Fase 2.1). Ahora se requiere cerrar lapsos, calcular notas anuales y decidir la promoción o repitencia del estudiante.

## Objetivo

Permitir a coordinación/dirección cerrar un lapso (congelar notas), calcular la nota definitiva anual por materia y el promedio general, y gestionar la promoción, repitencia y materias pendientes (arrastre).

## Alcance

- Cierre de lapso con congelamiento de `StudentScore`.
- Cálculo de `AnnualSubjectScore` y `AnnualAverage` al cerrar el período.
- Decisión de promoción (`StudentPromotion`).
- Registro de materias pendientes (`RecoveryRegistration`).
- Tests Feature para el flujo completo.

## Fuera de alcance

- Generación de PDFs / boletines (Fase 2.3).
- Re-inscripción automática desde promoción (mantiene Enrollment existente).

## Modelo de datos

### Cambios en `terms`

- `closing_date` (date nullable): fecha límite de carga de notas.
- `is_closed` (boolean default false): indica si el lapso está cerrado.

### `AnnualSubjectScore`

| Campo | Descripción |
|---|---|
| `student_id` | FK |
| `subject_id` | FK |
| `academic_period_id` | FK |
| `final_score` | Promedio ponderado de lapsos (1–20 o letra) |
| `letter_grade` | A–E |
| `status` | `promoted`, `recovery`, `failed` |
| `is_pending` | Pendiente por arrastre |

### `AnnualAverage`

| Campo | Descripción |
|---|---|
| `student_id` | FK |
| `academic_period_id` | FK |
| `average_score` | Promedio general del año |
| `letter_grade` | A–E |
| `status` | `promoted`, `repeating`, `conditional` |

### `StudentPromotion`

| Campo | Descripción |
|---|---|
| `student_id` | FK |
| `academic_period_id` | FK |
| `from_grade_id` | FK |
| `to_grade_id` | FK nullable |
| `status` | `promoted`, `repeating`, `conditional` |
| `decision` | texto justificativo |
| `decided_by` | FK usuario |
| `decided_at` | datetime |

### `RecoveryRegistration`

| Campo | Descripción |
|---|---|
| `student_id` | FK |
| `subject_id` | FK |
| `academic_period_id` | FK |
| `status` | `pending`, `passed`, `failed` |
| `recovery_score` | decimal nullable |

## Reglas de negocio

1. Un lapso cerrado no permite modificar `StudentScore`.
2. `AnnualSubjectScore.final_score` = suma de `StudentScore.score * Term.weight / 100`.
3. Si `AnnualSubjectScore` >= 10 y no es `is_pending` → materia aprobada.
4. Promedio general = promedio aritmético de `AnnualSubjectScore.final_score`.
5. Promoción:
   - Promedio >= 10 y 0 materias en `recovery`/`failed` → `promoted`.
   - Promedio < 10 o 3+ materias `failed` → `repeating`.
   - 1–2 materias `failed` → `conditional` (debe repararlas).
6. `RecoveryRegistration` se crea para cada materia `failed`.

## Endpoints API

- `POST /api/v1/terms/{id}/close`
- `POST /api/v1/academic-periods/{id}/close`
- `GET /api/v1/annual-subject-scores`
- `GET /api/v1/annual-averages`
- `POST /api/v1/students/{id}/calculate-promotion`
- `POST /api/v1/students/{id}/promotions`
- `POST /api/v1/recovery-registrations/{id}/grade`

## Tests

- Cierre de lapso congela notas.
- Cálculo de nota anual por materia.
- Promedio general.
- Promoción automática.
- Materia pendiente y nota de reparación.
