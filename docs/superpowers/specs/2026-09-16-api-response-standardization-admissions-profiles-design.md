# Fase 6.4 — Estandarización API: admisiones y perfiles

## Objetivo

Aplicar el contrato estándar de respuestas JSON a los módulos de admisiones y perfiles integrales sin alterar sus reglas, permisos ni experiencia visual.

## Alcance

Este lote incluye:

- `AdmissionController`
- `StudentProfileController`
- Consumidores frontend de sus endpoints
- Pruebas Feature de admisiones y perfiles

Disciplina, justificativos, mensajería, notificaciones y coordinación quedan para lotes posteriores.

## Contrato

### Operaciones exitosas

Los recursos y resultados usarán:

```json
{
  "success": true,
  "data": {},
  "message": "Operación completada exitosamente"
}
```

Las creaciones conservarán `201 Created`.

### Listados

El listado de admisiones usará `sendPaginatedResponse()` y conservará los campos del paginador de Laravel en el nivel raíz, junto con `success` y `message`. Los registros continuarán dentro de `data`.

### Errores

Los errores controlados usarán `sendError()`. Los errores automáticos de validación y autorización de Laravel conservarán sus códigos y estructura propios; no se modificará globalmente el manejador de excepciones en este lote.

## AdmissionController

- `index`: responder mediante `sendPaginatedResponse()` y mantener el filtro por estado.
- `store`: responder mediante `sendResponse()` con código `201`.
- `show`: envolver la admisión y sus relaciones en `data`.
- `update`: devolver la admisión actualizada mediante `sendResponse()`.
- `approve`: devolver la matrícula creada mediante `sendResponse()` con código `201`.
- `reject`: devolver la admisión rechazada mediante `sendResponse()`.
- `suggestSections`: devolver el arreglo de secciones sugeridas dentro de `data`.
- Preservar Policies, validaciones y operaciones de `AdmissionService`.

## StudentProfileController

- `show`: devolver `{ profile, documents }` dentro de `data`.
- `update`: devolver el perfil actualizado dentro de `data`.
- `storeDocument`: devolver el documento creado mediante `sendResponse()` con código `201`.
- `destroyDocument`: devolver una respuesta estándar exitosa con `data: null`.
- `verifyDocument`: devolver el documento verificado dentro de `data`.
- Preservar Gates, Policies, reglas de carga y operaciones de `StudentProfileService`.

## Frontend

Se localizarán todos los consumidores Vue y TypeScript de ambos módulos.

- Los consumidores basados en `useApi()` deberían seguir funcionando porque el composable extrae `data` automáticamente.
- Los consumidores que usen `fetch()` directamente se adaptarán para leer `data`.
- No se modificarán componentes, estilos ni flujos visuales fuera de lo necesario para conservar compatibilidad.

## Pruebas

Las pruebas verificarán:

- Estructura estándar y paginación del listado de admisiones.
- Estructura y mensajes de creación, consulta, actualización, aprobación, rechazo y sugerencias.
- Estructura del perfil completo.
- Actualización del perfil.
- Creación, verificación y eliminación de documentos.
- Conservación de los códigos HTTP `200` y `201`.
- Conservación de autorización y validación existentes.

## Verificación

```bash
docker exec sabere-backend-sabere.test-1 ./vendor/bin/pint
docker exec sabere-backend-sabere.test-1 php artisan test
docker exec sabere-backend-sabere.test-1 npm run build
```

## Compatibilidad

Backend, consumidores y pruebas se actualizarán juntos. No se mantendrán dos contratos paralelos ni se crearán rutas v2. El cambio se limita al envoltorio JSON y no altera los datos internos de los recursos.
