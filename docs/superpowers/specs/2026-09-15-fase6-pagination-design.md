# Fase 6 — Entrega 2: Paginación consistente en la API

## Resumen

Asegurar que todos los endpoints de listado de la API devuelvan una respuesta paginada con el mismo formato, parámetros `page` y `per_page` controlados, y límites razonables.

---

## Alcance

### Dentro del alcance

- Revisar todos los controladores `app/Http/Controllers/Api/V1/**/*.php` con métodos `index`.
- Garantizar que todo `index` use `paginate()` con límite máximo de 50 elementos por página y default 15.
- Responder siempre con el mismo formato de paginación de Laravel (data, links, meta).
- Agregar `per_page` a los requests como query string.
- Ajustar tests para asumir paginación cuando corresponda.
- Agregar `PaginationTest` de regresión para endpoints críticos.

### Fuera del alcance

- Cambiar la estructura de la respuesta a un formato de paginación personalizado.
- Modificar web/Inertia que ya usan datos propios del controlador web.

---

## 1. Controladores a revisar

| Controller | Estado estimado |
|---|---|
| `Academic/DocumentController` | revisar |
| `Academic/ScheduleController` | revisar |
| `Academic/StudentGuardianController` | revisar |
| `Academic/StudentScoreController` | revisar |
| `Academic/TaskController` | revisar |
| `Academic/TaskSubmissionController` | revisar |
| `Admin/RoleController` | revisar |
| `Admin/UserController` | revisar |
| `AdmissionController` | OK (paginate 20) |
| `DashboardController` | OK |
| `DirectMessageController` | revisar |
| `DisciplinaryRecordController` | OK (paginate 15) |
| `JustificationController` | OK (paginate 15) |
| `NotificationPreferenceController` | no aplica |
| `PushSubscriptionController` | no aplica |
| `StudentProfileController` | revisar |

---

## 2. Comportamiento

- `per_page` default: `15`.
- `per_page` máximo: `50`.
- Si el usuario pide más de 50, se limita a 50.
- Si `page` no se envía, se asume 1.
- La respuesta usa `LengthAwarePaginator` mediante `$query->paginate($perPage)`.

---

## 3. Helper

Agregar `IndexPaginationService` o un `Request` macro con `perPage()` es excesivo. Se usará directamente un helper en cada controlador:

```php
$perPage = min($request->integer('per_page', 15), 50);
```

---

## 4. Tests

- `PaginationTest` con casos para `/api/v1/users`, `/api/v1/tasks`, `/api/v1/messages`, etc.
- Verificar que `per_page` > 50 se limita.
- Verificar que `per_page` personalizado funciona.

---

## 5. Verificación

```bash
./vendor/bin/pint
php artisan test
npx vue-tsc --noEmit
npm run build
```
