# Fase 4 — Comunicación y experiencia

## Resumen

Tres subsistemas para mejorar la comunicación y experiencia de Sabere en el contexto venezolano:

1. **Web Push (4.1)** — notificaciones push del navegador/PWA para eventos críticos.
2. **Offline-first (4.2)** — service worker con cache estratégico y cola offline.
3. **Mensajería directa (4.3)** — mensajes entre representantes, profesores y coordinación.

---

## 4.1 Web Push Notifications

### Dependencias

- Composer: `minishlink/web-push`
- Generación de VAPID keys vía comando artisan

### Nuevos modelos y migraciones

**`push_subscriptions`**
- `id`
- `user_id` (foreign key + index)
- `endpoint` (string/text, unique)
- `p256dh_key` (string)
- `auth_token` (string)
- `subscribed_at` (timestamp)
- timestamps

**`user_notification_preferences`**
- `id`
- `user_id` (foreign key + unique)
- `push_scores` (boolean, default true)
- `push_tasks` (boolean, default true)
- `push_circulars` (boolean, default true)
- `push_events` (boolean, default true)
- `push_reenrollment` (boolean, default true)
- `push_messages` (boolean, default true)
- timestamps

### Nuevos archivos

```
app/Models/PushSubscription.php
app/Models/UserNotificationPreference.php
app/Services/WebPushService.php
app/Http/Controllers/Api/V1/PushSubscriptionController.php
app/Http/Controllers/Api/V1/NotificationPreferenceController.php
app/Console/Commands/WebPushGenerateKeysCommand.php
app/Http/Requests/Api/V1/StorePushSubscriptionRequest.php
app/Http/Requests/Api/V1/UpdateNotificationPreferenceRequest.php
```

### Endpoints API

| Método | Ruta | Descripción |
|---|---|---|
| POST | `/api/v1/push-subscriptions` | Registrar suscripción push |
| DELETE | `/api/v1/push-subscriptions` | Eliminar suscripción push |
| GET | `/api/v1/notification-preferences` | Ver preferencias del usuario |
| PUT | `/api/v1/notification-preferences` | Actualizar preferencias |

### Eventos push por defecto

- `scores_finalized` — notas finalizadas
- `circular_created` — circulares nuevas
- `reenrollment_approved` / `reenrollment_rejected` — re-inscripción
- `direct_message_received` — mensaje directo

Cada usuario puede desactivar cualquiera desde el perfil.

### Modificaciones

- `NotificationService::createNotification` agrega una llamada a `WebPushService::sendToUser()` cuando el evento está habilitado para push.
- `public/sw.js` parsea el payload `json` y muestra notificación con título, body, ícono y redirige al `url` al hacer click.

### Comando

`php artisan webpush:generate-keys` genera par de VAPID y los escribe en `.env` y muestra `VAPID_PUBLIC_KEY`.

---

## 4.2 Offline-first

### Mejoras al service worker

- Importar Workbox desde CDN en `public/sw.js`.
- Estrategias:
  - `Network First` para `/api/v1/*`
  - `Stale While Revalidate` para `/dashboard`, `/login`
  - `Cache First` para assets estáticos de `build/` e iconos
- Páginas precache: `/`, `/offline.html` (ya existe placeholder)
- Caché de datos del usuario: horario, tareas, circulares, boletín

### Cola offline

- Nuevo endpoint `POST /api/v1/offline/sync` recibe un array de acciones pendientes.
- Cada acción tiene: `type`, `payload`, `timestamp`, `local_id`.
- Tipos soportados:
  - `task_submission` — entrega de tarea
  - `attendance` — registro de asistencia por profesor
- Respuesta incluye `local_id` -> `server_id` o error.

### Frontend

- Composable `useOfflineQueue()` usando `localStorage`/IndexedDB.
- Interceptor de respuesta 5xx/timeout para guardar en cola automáticamente.
- Indicador visual de estado de conexión.

---

## 4.3 Mensajería Directa

### Nuevos modelos y migraciones

**`direct_messages`**
- `id`
- `sender_id` (FK users)
- `recipient_id` (FK users)
- `subject` (string, nullable)
- `body` (text)
- `read_at` (timestamp, nullable)
- `parent_id` (FK direct_messages, nullable, para hilos)
- `attachment_path` (string, nullable)
- `attachment_name` (string, nullable)
- timestamps
- soft deletes

### Autorización

- Representante puede escribir a: profesores/asignados de sus hijos, coordinadores, directores, admin.
- Profesor puede escribir a: representantes de estudiantes de sus secciones, coordinadores, directores, admin.
- Coordinador/Director/Admin pueden escribir a cualquier usuario.

### Endpoints API

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/api/v1/messages` | Bandeja de entrada (paginado) |
| GET | `/api/v1/messages/sent` | Enviados (paginado) |
| GET | `/api/v1/messages/{id}` | Ver mensaje |
| POST | `/api/v1/messages` | Enviar mensaje |
| POST | `/api/v1/messages/{id}/read` | Marcar como leído |
| GET | `/api/v1/messages/recipients` | Lista de posibles destinatarios |

### Web (Inertia)

```
resources/js/Pages/Messages/Index.vue
resources/js/Pages/Messages/Show.vue
resources/js/Pages/Messages/Compose.vue
```

- Sidebar: Inbox, Enviados, Nuevo
- Badge no leídos en sidebar principal
- Al recibir mensaje nuevo se envía push notification (WebPushService)

### Notificaciones push

Cada mensaje directo genera push al destinatario con:
- Título: `Nuevo mensaje de {sender}`
- Body: `subject` o primeras 80 chars de `body`
- Url: `/messages/{id}`

---

## Tests

- `tests/Feature/Api/V1/PushSubscriptionTest.php`
- `tests/Feature/Api/V1/NotificationPreferenceTest.php`
- `tests/Feature/Api/V1/DirectMessageTest.php`
- `tests/Feature/Console/WebPushGenerateKeysTest.php`
- `tests/Unit/Services/WebPushServiceTest.php` (sin envío real)

---

## Verificación

```bash
./vendor/bin/pint
php artisan test
npx vue-tsc --noEmit
npm run build
```
