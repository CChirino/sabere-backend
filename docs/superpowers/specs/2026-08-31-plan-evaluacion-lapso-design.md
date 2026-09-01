# Diseño — Plan de evaluación por lapso

## Contexto

El sistema de calificaciones actual almacena una única `StudentScore` por estudiante, materia y lapso. No existe aún un plan de evaluación que desglose las notas en ítems ponderados (exámenes, trabajos, participaciones, etc.), ni un flujo de aprobación por parte de coordinación antes de cargar notas.

## Objetivo

Permitir que el profesor defina un plan de evaluación por lapso con ítems cuyos pesos sumen el 100%, que el coordinador/director apruebe ese plan, y que las notas por ítem se traduzcan automáticamente en la nota definitiva del lapso en `StudentScore`. El plan debe soportar evaluación cualitativa (A–E) en Primaria y cuantitativa (1–20) en Secundaria.

## Alcance

- Creación, edición, envío, aprobación y rechazo de planes de evaluación.
- Carga de notas por ítem aprobado.
- Cálculo automático de `StudentScore` desde `StudentEvaluationScore`.
- Soporte de letras (Primaria) y números (Secundaria).
- Tests Feature para validación, aprobación y cálculo.

## Fuera de alcance

- Cierre formal del lapso y nota definitiva anual (Fase 2.2).
- Generación de PDFs / boletines (Fase 2.3).
- Historial de versiones del plan.

## Modelo de datos

### `EvaluationPlan`

| Campo | Tipo | Descripción |
|---|---|---|
| `academic_period_id` | FK | Período académico. |
| `term_id` | FK | Lapso. |
| `subject_id` | FK | Materia. |
| `grade_id` | FK | Grado/año. |
| `section_id` | FK nullable | Sección. Si es `null`, el plan aplica a todas las secciones de la materia/año (Secundaria). Si está presente, es por sección (Primaria). |
| `status` | string | `draft`, `submitted`, `approved`, `rejected`. |
| `submitted_at` | datetime nullable | Cuando el profesor lo envió. |
| `approved_by` | FK nullable | Usuario que aprobó/rechazó. |
| `approved_at` | datetime nullable | Fecha de aprobación/rechazo. |
| `notes` | text nullable | Observaciones de aprobación/rechazo. |

### `EvaluationItem`

| Campo | Tipo | Descripción |
|---|---|---|
| `evaluation_plan_id` | FK | Plan padre. |
| `name` | string | Nombre del ítem (ej. Examen I, Trabajo grupal). |
| `type` | string | `exam`, `quiz`, `project`, `homework`, `participation`, `other`. |
| `evaluation_mode` | string | `qualitative` o `quantitative`. |
| `weight` | decimal(5,2) | Peso del ítem. |
| `max_score` | decimal(5,2) nullable | Puntaje máximo. |
| `order` | integer | Orden de presentación. |
| `evaluation_date` | date nullable | Fecha de evaluación. |

### `StudentEvaluationScore`

| Campo | Tipo | Descripción |
|---|---|---|
| `student_id` | FK | Estudiante. |
| `subject_assignment_id` | FK | Asignación materia-sección-profesor. |
| `evaluation_item_id` | FK | Ítem del plan. |
| `score` | decimal(5,2) nullable | Nota numérica, requerida si `evaluation_mode = quantitative`. |
| `letter_grade` | char(1) nullable | Letra A–E, requerida si `evaluation_mode = qualitative`. |
| `graded_by` | FK | Profesor que calificó. |
| `graded_at` | datetime | Fecha de calificación. |
| `observations` | text nullable | Observaciones. |

### `StudentScore` (existente, ajustado)

- Sigue siendo la nota definitiva del lapso.
- `score` se recalcula automáticamente a partir de `StudentEvaluationScore`.
- `letter_grade` se mantiene derivada del `score`.

## Reglas de negocio

1. Suma de los `weight` de los ítems de un plan debe ser exactamente 100%.
2. Solo se pueden cargar notas por ítem cuando el plan esté `approved`.
3. Modificar los ítems o pesos después de aprobar invalida el plan (vuelve a `draft`) o requiere re-aprobar.
4. Conversión de letras a números (Primaria): `A=19, B=16, C=13, D=10, E=5` (configurable futuro).
5. Para Secundaria, el promedio ponderado se calcula sobre `score`.
6. El recálculo de `StudentScore` se ejecuta cuando se guardan notas por ítem o cuando cambian pesos del plan.
7. Audit log (`LogsActivity`) en `StudentEvaluationScore` y `StudentScore`.

## Flujo

1. Profesor crea plan en `draft` con ítems.
2. Profesor lo envía (`submitted`).
3. Coordinador/director revisa y aprueba (`approved`) o rechaza (`rejected`).
4. Con `approved`, el profesor carga `StudentEvaluationScore`.
5. Cada guardado recalcula `StudentScore` para cada estudiante afectado.
6. Cuando se cierren lapsos (Fase 2.2), se congela `is_final`.

## Endpoints API nuevos

- `GET /api/v1/evaluation-plans`
- `POST /api/v1/evaluation-plans`
- `GET /api/v1/evaluation-plans/{id}`
- `PUT /api/v1/evaluation-plans/{id}`
- `DELETE /api/v1/evaluation-plans/{id}`
- `POST /api/v1/evaluation-plans/{id}/submit`
- `POST /api/v1/evaluation-plans/{id}/approve`
- `POST /api/v1/evaluation-plans/{id}/reject`
- `POST /api/v1/evaluation-plans/{id}/recalculate`
- `GET /api/v1/student-evaluation-scores`
- `POST /api/v1/student-evaluation-scores`
- `PUT /api/v1/student-evaluation-scores/{id}`

## Tests Feature

1. Suma de pesos distinta de 100% es rechazada.
2. Envío y aprobación del plan.
3. No se cargan notas si el plan no está aprobado.
4. Cálculo correcto del promedio ponderado en Secundaria.
5. Conversión y cálculo correcto en Primaria con letras.
6. Actualización automática de `StudentScore` al guardar ítems.
7. Políticas: profesor solo en su asignación, coordinador/director pueden aprobar.

## Consideraciones

- Se añadirá `HasFactory` a los nuevos modelos y factories para tests.
- No se eliminan datos existentes; `StudentScore` sigue siendo la fuente de la nota definitiva.
