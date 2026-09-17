# Fase 6.4 — Estandarización API: mensajería y notificaciones

## Objetivo

Aplicar el contrato estándar de respuestas JSON a mensajería directa, preferencias de notificación y suscripciones Web Push sin modificar permisos, adjuntos ni entrega de notificaciones.

## Alcance

Este lote incluye:

- `DirectMessageController`
- `NotificationPreferenceController`
- `PushSubscriptionController`
- Consumidores Vue y composables relacionados
- Pruebas Feature de mensajes, preferencias y suscripciones

Coordinación queda para el lote final.

## Contrato

Las operaciones exitosas devolverán `success`, `data` y `message`. Los listados paginados mantendrán los campos del paginador en el nivel raíz, junto con `success` y `message`. Las operaciones sin recurso de retorno usarán `data: null`.

Los errores controlados usarán `sendError()`. Las respuestas automáticas de validación y autorización conservarán el comportamiento de Laravel.

## Mensajería directa

- `index`: usar `sendPaginatedResponse()` para la bandeja de entrada.
- `sent`: usar `sendPaginatedResponse()` para mensajes enviados.
- `show`: devolver el mensaje, remitente, destinatario y respuestas dentro de `data`.
- `store`: usar `sendResponse()` con código `201`; conservar adjuntos, validación de destinatario y Web Push.
- Error por destinatario no permitido: usar `sendError()` con código `403`.
- `markAsRead`: devolver el mensaje actualizado dentro de `data`.
- `recipients`: devolver la colección permitida dentro de `data`.
- `unreadCount`: devolver `{ count }` dentro de `data`.

## Preferencias de notificación

- `index`: devolver las preferencias del usuario dentro de `data`.
- `update`: devolver las preferencias actualizadas dentro de `data`.
- Mantener validaciones booleanas y creación automática de preferencias.

## Suscripciones Web Push

- `store`: conservar `updateOrCreate`, devolver la suscripción resultante dentro de `data` y un mensaje estándar.
- `destroy`: devolver `data: null` y un mensaje estándar.
- Mantener validación del endpoint y claves criptográficas.
- No exponer claves privadas ni información adicional en logs o respuestas.

## Frontend

Se revisarán las páginas de mensajes, preferencias y el composable de Web Push:

- `useApi()` continuará desempaquetando `data` automáticamente.
- Los consumidores de listados se adaptarán si intentan acceder nuevamente a `.data`.
- Los contadores deberán leer el objeto `{ count }` desempaquetado.
- No se modificarán estilos ni flujos visuales.

## Pruebas

Se verificará:

- Contrato y paginación de recibidos y enviados.
- Contrato al enviar, consultar y marcar mensajes como leídos.
- Error estándar al intentar enviar a un destinatario no permitido.
- Contrato de destinatarios y contador de no leídos.
- Consulta y actualización de preferencias.
- Registro y eliminación de suscripciones Push.
- Conservación de autorización, adjuntos y comportamiento de notificaciones existente.

## Verificación

```bash
docker exec sabere-backend-sabere.test-1 ./vendor/bin/pint
docker exec sabere-backend-sabere.test-1 php artisan test
docker exec sabere-backend-sabere.test-1 npm run build
```

## Compatibilidad

Backend, frontend y pruebas se actualizarán juntos. No habrá rutas v2 ni contratos paralelos. El cambio se limita al envoltorio JSON y a los consumidores afectados.
