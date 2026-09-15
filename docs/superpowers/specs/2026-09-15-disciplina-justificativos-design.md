# Fase 5 — Entrega 2: Disciplina y Justificativos

## Resumen

Registrar incidencias disciplinarias de los estudiantes clasificadas por gravedad (leve, moderada, grave) y permitir a los representantes solicitar justificativos de inasistencias, que el staff revisa y aprueba, vinculando el resultado al registro de asistencia existente.

---

## Alcance

### Dentro del alcance

- Registro de incidencias disciplinarias con tipo, gravedad, descripción, fecha y acción tomada.
- Tipos de incidencia configurables por el colegio (`incident_types`).
- Listado, filtrado y detalle de incidencias por rol.
- Solicitud de justificativos de inasistencia por parte del representante.
- Revisión y aprobación/rechazo de justificativos por staff.
- Al aprobar, el registro de asistencia asociado cambia a `excused` y se recalcula el porcentaje de asistencia.
- Notificaciones push al representante y al estudiante en incidencias graves; notificación al staff cuando llega un nuevo justificativo.
- API y páginas Inertia para ambos módulos.
- Tests Feature para autorización y flujos principales.

### Fuera del alcance

- Cálculo de conducta global o nota de disciplina en boletines.
- Sanciones o medidas correctivas con seguimiento de cumplimiento.
- Dashboard de indicadores (otra entrega).
- Pagos o facturación.

---

## 1. Módulo de disciplina

### Modelos y migraciones

**`incident_types`**
- `id`
- `name`
- `default_severity` (`leve`, `moderada`, `grave`)
- `requires_notification` (boolean, default false)
- `description` (nullable)
- `is_active` (boolean, default true)
- timestamps
- soft deletes

**`disciplinary_records`**
- `id`
- `student_id` (FK users)
- `section_id` (FK sections, nullable)
- `academic_period_id` (FK academic_periods)
- `recorded_by` (FK users)
- `incident_type_id` (FK incident_types)
- `severity` (`leve`, `moderada`, `grave`)
- `description` (text)
- `action_taken` (text, nullable)
- `date` (date)
- `is_private` (boolean, default false) — solo visible para staff
- timestamps
- soft deletes

### Comportamiento

- `teacher` puede registrar incidencias de los estudiantes de sus secciones.
- `coordinator`/`director`/`admin` pueden registrar y ver todas las incidencias.
- `student` ve sus propias incidencias, excepto las marcadas como privadas.
- `guardian` ve las incidencias de sus hijos, excepto las privadas.
- Las incidencias con `severity = grave` envían push a representante y estudiante.
- Listados filtrables por gravedad, tipo, sección y rango de fechas.

### API

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/api/v1/disciplinary-records` | Listar incidencias (según rol y filtros) |
| POST | `/api/v1/disciplinary-records` | Crear incidencia |
| GET | `/api/v1/disciplinary-records/{record}` | Ver detalle |
| PUT | `/api/v1/disciplinary-records/{record}` | Editar incidencia |
| DELETE | `/api/v1/disciplinary-records/{record}` | Eliminar (soft delete) |

### Web (Inertia)

- `Discipline/Index.vue` — listado de incidencias.
- `Discipline/Create.vue` — registrar incidencia.
- `Discipline/Show.vue` — detalle.

---

## 2. Módulo de justificativos

### Modelos y migraciones

**`justifications`**
- `id`
- `student_id` (FK users)
- `guardian_id` (FK users)
- `attendance_id` (FK attendances, nullable)
- `academic_period_id` (FK academic_periods)
- `start_date` (date)
- `end_date` (date)
- `reason` (text)
- `document_path` (string, nullable)
- `status` (`pending`, `approved`, `rejected`)
- `reviewed_by` (FK users, nullable)
- `review_notes` (text, nullable)
- `reviewed_at` (datetime, nullable)
- timestamps
- soft deletes

### Comportamiento

- El representante (`guardian`) crea justificativos para sus hijos.
- Puede adjuntar un documento opcional.
- El rango `start_date` / `end_date` indica el período cubierto.
- Staff (`admin`, `director`, `coordinator`) revisa y aprueba/rechaza.
- Al aprobar:
  - Para cada registro de `Attendance` del estudiante entre `start_date` y `end_date` con `status = absent`, se cambia a `excused`.
  - Se recalcula el porcentaje de asistencia del estudiante en el período.
  - Se notifica al representante y al estudiante.
- Si se rechaza, se guarda el motivo y se notifica al representante.

### API

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/api/v1/justifications` | Listar (filtrado por rol) |
| POST | `/api/v1/justifications` | Crear justificativo |
| GET | `/api/v1/justifications/{justification}` | Ver detalle |
| POST | `/api/v1/justifications/{justification}/approve` | Aprobar |
| POST | `/api/v1/justifications/{justification}/reject` | Rechazar |

### Web (Inertia)

- `Justifications/Index.vue` — listado.
- `Justifications/Create.vue` — crear justificativo.
- `Justifications/Review.vue` — revisar (staff).

---

## 3. Arquitectura

### Servicios

- `DisciplineService` — creación, consulta y notificación de incidencias.
- `JustificationService` — aprobación/rechazo, actualización de asistencia y notificaciones.

### Policies

- `DisciplinaryRecordPolicy` — staff y profesor de la sección pueden registrar; estudiante/representante ven solo lo propio.
- `JustificationPolicy` — representante crea para sus hijos; staff aprueba/rechaza.
- `IncidentTypePolicy` — staff puede administrar catálogo.

### Relaciones

- `User` → `disciplinaryRecords` (como estudiante)
- `User` → `recordedDisciplinaryRecords` (como registrador)
- `User` → `justifications` (como estudiante)
- `User` → `submittedJustifications` (como representante)
- `DisciplinaryRecord` → `student`, `recordedBy`, `section`, `academicPeriod`, `incidentType`
- `Justification` → `student`, `guardian`, `attendance`, `academicPeriod`, `reviewedBy`

---

## 4. Tests

- `DisciplineTest`: profesor crea incidencia; estudiante ve solo la suya; representante ve la de su hijo; privadas no visibles.
- `DisciplineNotificationTest`: incidencia grave dispara notificación push.
- `JustificationTest`: representante crea; staff aprueba y asistencia pasa a `excused`; rechazo notifica.
- `JustificationAuthorizationTest`: representante no puede aprobar; estudiante no puede crear.

---

## 5. Verificación

```bash
./vendor/bin/pint
php artisan test
npx vue-tsc --noEmit
npm run build
```
