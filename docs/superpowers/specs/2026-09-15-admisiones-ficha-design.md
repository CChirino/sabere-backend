# Fase 5 — Entrega 1: Admisiones y Ficha integral del estudiante

## Resumen

Permitir que el personal del colegio registre solicitudes de admisión de nuevos estudiantes, las revise con recaudos digitalizados y, al aprobarlas, convierta automáticamente la solicitud en usuario estudiante, representante y matrícula. Una vez aprobada la admisión, el representante puede completar y mantener la ficha integral del estudiante (datos médicos, autorizaciones de retiro y documentos), bajo revisión y verificación del staff.

---

## Alcance

### Dentro del alcance

- Solicitud de admisión creada por staff.
- Carga de documentos/recaudos en la solicitud.
- Flujo de aprobación/rechazo de admisiones.
- Conversión a usuario, representante, relación estudiante-representante y matrícula.
- Sugerencia automática de sección con cupo, pero la decisión final es manual del staff.
- Ficha integral del estudiante (datos médicos, emergencias, autorizados a retirar).
- Documentos de la ficha (carnet, seguro, vacunas, etc.) con verificación por staff.
- API y páginas web Inertia para ambos módulos.
- Tests Feature para autorización y flujo de aprobación.

### Fuera del alcance

- Formulario público de admisión sin login.
- Pagos, mensualidades o cuotas.
- Módulos de disciplina, justificativos o dashboard de indicadores (otras entregas de Fase 5).

---

## 1. Admisiones

### Modelos y migraciones

**`student_applications`**
- `id`
- `academic_period_id` (FK)
- `grade_id` (FK, grado solicitado)
- `first_name`, `last_name`
- `birth_date`
- `gender`
- `id_number` (cédula escolar / identificación)
- `nationality` (nullable)
- `address` (nullable)
- `current_school` (nullable)
- `status`: `pending`, `approved`, `rejected`
- `notes` (nullable)
- `rejection_reason` (nullable)
- `processed_by` (FK users, nullable)
- `processed_at` (nullable)
- `created_by` (FK users)
- timestamps
- soft deletes

**`student_application_guardians`**
- `id`
- `student_application_id` (FK)
- `first_name`, `last_name`
- `id_number`
- `email`
- `phone`
- `relationship`
- `is_primary` (boolean)
- `address` (nullable)
- timestamps

**`student_application_documents`**
- `id`
- `student_application_id` (FK)
- `type` (foto, partida, cedula_escolar, fe_bautismo, otros)
- `path`
- `original_name`
- `mime`
- `description` (nullable)
- timestamps

### Comportamiento

- Solo usuarios con rol `admin`, `director` o `coordinador` pueden crear, editar, aprobar o rechazar admisiones.
- La solicitud inicia en `pending`.
- Al aprobar:
  - El sistema recomienda secciones del grado solicitado con cupo disponible, pero el staff elige manualmente la `section_id` final.
  - Crea `User` con rol `student`.
  - Si el representante no existe por email, crea `User` con rol `guardian`.
  - Vincula estudiante y representante en `student_guardians`.
  - Crea `Enrollment` activa en el período y sección elegidos.
  - Copia los documentos de la admisión a `student_documents` con `is_verified = false`.
  - Crea `StudentProfile` vacío para el estudiante.
  - Cambia `status` a `approved`, guarda `processed_by` y `processed_at`.
- Al rechazar: `status = rejected`, `rejection_reason` obligatorio, guarda `processed_by` y `processed_at`.

### API

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/api/v1/admissions` | Listar solicitudes (paginadas, filtrables por estado) |
| POST | `/api/v1/admissions` | Crear solicitud |
| GET | `/api/v1/admissions/{admission}` | Ver detalle |
| PUT | `/api/v1/admissions/{admission}` | Editar mientras esté `pending` |
| POST | `/api/v1/admissions/{admission}/approve` | Aprobar y convertir en matrícula |
| POST | `/api/v1/admissions/{admission}/reject` | Rechazar |

### Web (Inertia)

- `Admissions/Index.vue` — listado con filtros.
- `Admissions/Create.vue` — formulario de nueva solicitud.
- `Admissions/Show.vue` — detalle, documentos, acciones aprobar/rechazar.

---

## 2. Ficha integral del estudiante

### Modelos y migraciones

**`student_profiles`**
- `id`
- `user_id` (FK users, estudiante)
- `blood_type` (nullable)
- `allergies` (text, nullable)
- `medical_conditions` (text, nullable)
- `medications` (text, nullable)
- `dietary_restrictions` (text, nullable)
- `emergency_contact_name`
- `emergency_contact_phone`
- `emergency_contact_relationship` (nullable)
- `authorized_pickup` (JSON, nullable)
- `additional_notes` (text, nullable)
- timestamps

**`student_documents`**
- `id`
- `user_id` (FK users, estudiante)
- `type` (carnet, seguro, vacunas, identidad, otros)
- `path`
- `original_name`
- `mime`
- `description` (nullable)
- `is_verified` (boolean, default false)
- `verified_by` (FK users, nullable)
- `verified_at` (nullable)
- timestamps
- soft deletes

### Autorización

- Staff (`admin`, `director`, `coordinador`) puede ver y editar la ficha de cualquier estudiante.
- Representante (`guardian`) puede ver y editar la ficha de los estudiantes vinculados a él.
- Representante puede subir documentos, pero no marcar `is_verified`.
- Solo staff puede marcar documentos como verificados.

### API

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/api/v1/students/{student}/profile` | Ver ficha |
| PUT | `/api/v1/students/{student}/profile` | Actualizar ficha |
| POST | `/api/v1/students/{student}/documents` | Subir documento |
| DELETE | `/api/v1/students/{student}/documents/{document}` | Eliminar documento |
| POST | `/api/v1/students/{student}/documents/{document}/verify` | Verificar documento |

### Web (Inertia)

- `StudentProfiles/Show.vue` — ficha completa y documentos.
- `StudentProfiles/Edit.vue` — edición de datos y subida de documentos.

---

## 3. Arquitectura

### Servicios

- `AdmissionService` — lógica de creación, aprobación/rechazo, sugerencia de secciones.
- `StudentProfileService` — creación/actualización de ficha, subida de documentos.

### Policies

- `StudentApplicationPolicy` — staff total; estudiantes/representantes sin acceso.
- `StudentProfilePolicy` — staff o guardian de ese estudiante.
- `StudentDocumentPolicy` — staff o guardian del estudiante; `verify` solo staff.

### Relaciones

- `StudentApplication` → `guardians`, `documents`
- `User` → `studentProfile`, `studentDocuments`

---

## 4. Tests

- `AdmissionTest`: staff crea, edita, aprueba y rechaza; representante no puede acceder.
- `AdmissionApprovalTest`: al aprobar se crean usuario, relación y matrícula; se recomienda sección con cupo.
- `StudentProfileTest`: guardian edita ficha de su hijo, no de otro; staff verifica documento.

---

## 5. Verificación

```bash
./vendor/bin/pint
php artisan test
npx vue-tsc --noEmit
npm run build
```
