# Fase 6.4 — Estandarización API: coordinación

## Objetivo

Completar la estandarización gradual de la API separando la construcción de respuestas HTTP de los cálculos del módulo de coordinación.

## Alcance

Este lote incluye:

- `CoordinatorController`
- `CoordinatorDataService`
- Páginas Vue del módulo de coordinación
- Pruebas Feature de sus cuatro endpoints

Los `204 No Content` de eliminación de usuarios y roles permanecen como excepciones HTTP intencionales.

## Arquitectura

`CoordinatorDataService` dejará de devolver estructuras con `success`. El controlador será el único responsable del contrato HTTP.

- Los métodos paginados devolverán paginadores o estructuras de dominio sin envoltorio HTTP.
- Los cálculos de estadísticas permanecerán en el servicio.
- El caso de profesor inexistente se representará con `null` y el controlador responderá mediante `sendError()` con código `404`.

## Endpoints

### Profesores

`teachers()` devolverá un paginador estándar con profesores transformados. El controlador usará `sendPaginatedResponse()` y mantendrá búsqueda y `per_page`.

### Detalle del profesor

`teacherShow()` devolverá el profesor con asignaciones y estadísticas, o `null`. El controlador usará `sendResponse()` o `sendError('Profesor no encontrado', [], 404)`.

### Resumen de tareas

`tasksOverview()` devolverá un objeto de dominio con:

```json
{
  "items": [],
  "stats": {},
  "pagination": {}
}
```

El controlador lo envolverá mediante `sendResponse()`. Se conservarán filtros, conteos y paginación actuales.

### Resumen de notas

`scoresOverview()` devolverá un objeto equivalente con `items`, estadísticas y paginación. Se conservarán selección de lapso, promedios y filtros existentes.

## Frontend

Se revisarán las páginas de coordinación:

- El composable `useApi()` extraerá `data` automáticamente.
- Profesores consumirá directamente el arreglo de registros del paginador.
- Los resúmenes leerán `items`, `stats` y `pagination` desde el objeto desempaquetado.
- No se modificarán estilos ni comportamiento visual.

## Pruebas

Se añadirán pruebas para:

- Listado de profesores con contrato y paginación estándar.
- Búsqueda de profesores.
- Detalle exitoso y error estándar `404`.
- Contrato de resumen de tareas.
- Contrato de resumen de notas.
- Conservación de filtros, estadísticas y permisos de acceso.

## Verificación

```bash
docker exec sabere-backend-sabere.test-1 ./vendor/bin/pint
docker exec sabere-backend-sabere.test-1 php artisan test
docker exec sabere-backend-sabere.test-1 npm run build
```

## Resultado esperado

Después de este lote no quedarán respuestas `response()->json()` directas en controladores API v1, salvo los dos `204 No Content` intencionales de usuarios y roles.
