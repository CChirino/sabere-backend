# Fase 5 — Entrega 3: Dashboard de Indicadores

## Resumen

Página de indicadores operativos con gráficas, visible únicamente para staff (`admin`, `director`, `coordinator`). Agrupa datos de asistencia, calificaciones, disciplina, admisiones y justificativos del período académico seleccionado.

---

## Alcance

### Dentro del alcance

- Endpoint único `/api/v1/dashboard/indicators` que devuelve todas las métricas agregadas.
- Filtro por `academic_period_id` (default: período activo).
- Indicadores:
  - Promedio de asistencia por sección.
  - Secciones con menor asistencia (top 5).
  - Promedio de calificaciones por sección y por materia.
  - Estudiantes con asistencia < 75% o promedio < 10.
  - Incidencias disciplinarias por gravedad (leve, moderada, grave).
  - Tipos de incidencia más frecuentes.
  - Admisiones por estado (pending, approved, rejected).
  - Justificativos pendientes de revisión.
- Gráficas de barras, líneas y pastel usando Chart.js.
- Página Inertia `Dashboard/Indicators.vue`.
- Tests Feature de autorización y precisión de indicadores.

### Fuera del alcance

- Exportación a PDF/Excel en esta entrega.
- Filtros avanzados por fecha o comparación entre años.
- Notificaciones automáticas basadas en indicadores.

---

## 1. Modelo de datos

No se agregan tablas. Se reutilizan:

- `attendances` → asistencia por sección.
- `student_scores` → calificaciones.
- `disciplinary_records` → incidencias.
- `student_applications` → admisiones.
- `justifications` → justificativos.

---

## 2. Servicios

### `DashboardMetricService`

Responsable de calcular cada indicador. Métodos públicos:

- `attendanceBySection(int $academicPeriodId): Collection`
- `scoresBySection(int $academicPeriodId): Collection`
- `scoresBySubject(int $academicPeriodId): Collection`
- `atRiskStudents(int $academicPeriodId): Collection`
- `disciplinaryBySeverity(int $academicPeriodId): array`
- `disciplinaryByType(int $academicPeriodId): Collection`
- `admissionsByStatus(int $academicPeriodId): array`
- `pendingJustifications(int $academicPeriodId): int`
- `all(int $academicPeriodId): array` — consolida todo

---

## 3. API

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/api/v1/dashboard/indicators` | Métricas consolidadas. Query: `academic_period_id` |

Respuesta:

```json
{
  "academic_period_id": 1,
  "attendance_by_section": [...],
  "scores_by_section": [...],
  "scores_by_subject": [...],
  "at_risk_students": [...],
  "disciplinary_by_severity": { "leve": 5, "moderada": 2, "grave": 1 },
  "disciplinary_by_type": [...],
  "admissions_by_status": { "pending": 3, "approved": 8, "rejected": 1 },
  "pending_justifications": 4
}
```

---

## 4. Frontend

### Página Inertia

- `resources/js/Pages/Dashboard/Indicators.vue`
- Layout `AppLayout.vue`.
- Selector de período académico.
- Cards con contadores (admisiones, justificativos, estudiantes en riesgo).
- Gráficas:
  - Barras: asistencia por sección.
  - Barras: promedio de notas por sección/materia.
  - Líneas: inasistencias por semana.
  - Pastel: incidencias por gravedad.
- Tabla de estudiantes en riesgo con links a ficha/perfil.

### Librería

- `chart.js` v4.x + `vue-chartjs` (sin `react-chartjs-2`).

---

## 5. Autorización

- Solo `admin`, `director` y `coordinator`.
- Middleware `role:admin|director|coordinator`.

---

## 6. Ruta web

- `GET /indicators` → `DashboardController::indicators` → name `indicators.index`

---

## 7. Tests

- `DashboardIndicatorTest`:
  - staff accede a `/api/v1/dashboard/indicators`.
  - profesor no accede.
  - contadores de admisiones y justificativos son correctos.
  - estudiantes en riesgo se calculan correctamente.

---

## 8. Verificación

```bash
./vendor/bin/pint
php artisan test
npx vue-tsc --noEmit
npm run build
```
