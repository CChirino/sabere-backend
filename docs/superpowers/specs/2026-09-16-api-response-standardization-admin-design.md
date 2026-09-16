# Fase 6.4 — Estandarización de respuestas API: módulo Admin

## Objetivo

Estandarizar gradualmente las respuestas JSON de la API sin romper los consumidores existentes. Este primer lote cubre exclusivamente la administración de usuarios y roles.

## Alcance

Se modificarán:

- `Admin\UserController`
- `Admin\RoleController`
- Consumidores Vue de los endpoints de usuarios y roles
- Pruebas Feature relacionadas con usuarios, roles y paginación

No se modificarán en este lote admisiones, perfiles, disciplina, justificativos, mensajería, notificaciones ni coordinación.

## Contrato de respuestas

### Recursos individuales y operaciones exitosas

Las respuestas exitosas usarán:

```json
{
  "success": true,
  "data": {},
  "message": "Operación completada exitosamente"
}
```

Las creaciones conservarán el código HTTP `201`.

### Listados paginados

Los listados usarán `sendPaginatedResponse()` y conservarán los campos estándar del paginador de Laravel:

```json
{
  "current_page": 1,
  "data": [],
  "first_page_url": "...",
  "from": 1,
  "last_page": 1,
  "last_page_url": "...",
  "links": [],
  "next_page_url": null,
  "path": "...",
  "per_page": 15,
  "prev_page_url": null,
  "to": 1,
  "total": 1,
  "success": true,
  "message": "Listado obtenido exitosamente"
}
```

Se mantendrá el soporte para `per_page` y el límite máximo definido por `PaginationService`.

### Errores controlados

Los errores del dominio usarán:

```json
{
  "success": false,
  "message": "Descripción del error"
}
```

Se conservarán los códigos HTTP existentes, especialmente `403`, `404` y `422`.

### Eliminaciones

Las eliminaciones exitosas conservarán `204 No Content`. Al no admitir cuerpo, no usarán el envoltorio estándar. Esta excepción queda documentada como parte del contrato HTTP.

## Backend

### UserController

- `index`: usar `sendPaginatedResponse()`.
- `store`, `show` y `update`: usar `sendResponse()`.
- `destroy`: usar `sendError()` para restricciones y conservar `204` cuando la eliminación sea exitosa.
- Mantener las validaciones, permisos, sincronización de roles y protección de usuarios administrativos existentes.

### RoleController

- `index`: usar `sendPaginatedResponse()`.
- `store`, `show` y `update`: usar `sendResponse()`.
- `destroy`: usar `sendError()` para roles protegidos y conservar `204` cuando la eliminación sea exitosa.
- Mantener middleware, validaciones y sincronización de permisos existentes.

## Frontend

Se localizarán todos los consumidores de `/api/v1/admin/users` y `/api/v1/admin/roles`.

- Los recursos individuales se leerán desde `response.data`.
- Los listados seguirán leyendo el arreglo desde `response.data`, porque el paginador mantiene esa clave.
- Se revisarán estados, formularios y mensajes para evitar que interpreten el objeto envolvente como la entidad.
- No se rediseñarán las pantallas ni se cambiará su comportamiento visual.

## Pruebas

Se ampliarán las pruebas Feature para cubrir:

- Forma estándar del listado paginado de usuarios.
- Forma estándar del listado paginado de roles.
- Respuesta estándar al crear, consultar y actualizar recursos.
- Respuesta de error estándar al intentar eliminar usuarios o roles protegidos.
- Respuesta vacía `204` al eliminar recursos permitidos.
- Conservación de `per_page` y su límite máximo.

También se ejecutarán:

```bash
docker exec sabere-backend-sabere.test-1 ./vendor/bin/pint
docker exec sabere-backend-sabere.test-1 php artisan test
npm run build
```

## Compatibilidad y despliegue

Backend, frontend y pruebas se actualizarán en el mismo commit de implementación. No habrá un período con contratos duplicados ni se añadirá una API v2. El trabajo continuará por lotes después de verificar este módulo.

## Siguientes lotes

1. Admisiones y perfiles.
2. Disciplina y justificativos.
3. Mensajería y notificaciones.
4. Coordinación y endpoints restantes.
