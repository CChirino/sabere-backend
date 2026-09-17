# Fase 6.4 — Estandarización API: disciplina y justificativos

## Objetivo

Aplicar el contrato estándar de respuestas JSON a disciplina, tipos de incidencia y justificativos sin modificar reglas de autorización, privacidad, revisión o notificación.

## Alcance

Este lote incluye:

- `DisciplinaryRecordController`
- `IncidentTypeController`
- `JustificationController`
- Consumidores frontend de estos endpoints
- Pruebas Feature de disciplina y justificativos

Mensajería, notificaciones y coordinación quedan para lotes posteriores.

## Contrato

Las operaciones exitosas devolverán `success`, `data` y `message`. Los listados paginados conservarán los campos del paginador en el nivel raíz y añadirán `success` y `message`. Las eliminaciones devolverán `data: null` con un mensaje exitoso.

Los errores automáticos de validación y autorización de Laravel no se modificarán globalmente en este lote.

## Disciplina

### DisciplinaryRecordController

- `index`: usar `sendPaginatedResponse()` y conservar filtros por gravedad y estudiante.
- `store`: usar `sendResponse()` con código `201`.
- `show`: devolver la incidencia y relaciones dentro de `data`.
- `update`: devolver la incidencia actualizada dentro de `data`.
- `destroy`: devolver una respuesta estándar con `data: null`.
- Preservar la visibilidad por rol y la exclusión de incidencias privadas para estudiantes y representantes.
- Preservar Policies y operaciones de `DisciplineService`.

### IncidentTypeController

- Estandarizar el listado con `sendPaginatedResponse()` si usa paginación, o `sendResponse()` si el endpoint conserva una colección completa de catálogo.
- Mantener filtros, orden y autorización existentes.
- Estandarizar las demás operaciones existentes sin cambiar sus códigos HTTP.

## Justificativos

### JustificationController

- `index`: usar `sendPaginatedResponse()` y conservar filtros por estado y visibilidad por rol.
- `store`: usar `sendResponse()` con código `201`.
- `show`: devolver el justificativo y relaciones dentro de `data`.
- `approve` y `reject`: devolver el justificativo revisado dentro de `data`.
- `destroy`: devolver una respuesta estándar con `data: null`.
- Preservar Policies, validaciones y operaciones de `JustificationService`, incluido el cambio de asistencia al aprobar.

## Frontend

Se revisarán todos los consumidores Vue y TypeScript:

- Los consumidores basados en `useApi()` seguirán recibiendo directamente el contenido de `data`.
- Se corregirán consumidores que intenten acceder nuevamente a `.data` después del desempaquetado.
- Los consumidores con `fetch()` directo se adaptarán al nuevo envoltorio.
- No se modificarán componentes, estilos ni flujos visuales.

## Pruebas

Se ampliarán las pruebas para verificar:

- Contrato y paginación de los listados.
- Contrato de creación, consulta y actualización de incidencias.
- Contrato de eliminación de incidencias.
- Contrato del catálogo de tipos de incidencia.
- Contrato de creación, consulta, aprobación y rechazo de justificativos.
- Contrato de eliminación de justificativos.
- Conservación de privacidad, autorización y efectos sobre asistencia.

## Verificación

```bash
docker exec sabere-backend-sabere.test-1 ./vendor/bin/pint
docker exec sabere-backend-sabere.test-1 php artisan test
docker exec sabere-backend-sabere.test-1 npm run build
```

## Compatibilidad

Backend, frontend y pruebas se actualizarán juntos. No se crearán rutas v2 ni contratos paralelos. Los cambios se limitan al envoltorio JSON y a los consumidores que dependan de la forma anterior.
