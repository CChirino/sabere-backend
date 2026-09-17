# Fase 6.6 — Desacoplar Services: evaluación y calificaciones

## Objetivo

Eliminar dependencias de `Illuminate\Http\Request` y del facade `Auth` en los servicios de evaluación y calificaciones, trasladando responsabilidades HTTP a los controladores.

## Alcance

Este lote incluye:

- `EvaluationPlanService`
- `StudentScoreService`
- `StudentEvaluationScoreService`
- `ManualScoreService`
- Sus controladores API
- Pruebas Feature existentes y pruebas unitarias o directas de Services

No incluye estructura académica, matrículas, tareas, coordinación ni `PaginationService`.

## Principios

- Los controladores validan solicitudes y obtienen el usuario autenticado.
- Los controladores calculan `per_page` mediante `PaginationService`.
- Los Services reciben arrays validados, filtros simples, modelos, IDs y usuarios explícitos.
- Los Services no importan `Request` ni consultan `Auth`.
- Las reglas de negocio permanecen en los Services.
- Las Policies y respuestas HTTP permanecen en los controladores.

## Interfaces esperadas

### Consultas

Los métodos de consulta recibirán `User $user` y `array $filters`. Los filtros usarán las mismas claves aceptadas actualmente por los endpoints.

### Escritura

Los métodos de creación y actualización recibirán `array $validated` y, cuando corresponda, `User $actor`. El actor se usará para campos como `created_by`, `graded_by`, aprobaciones y auditoría.

### Paginación

`StudentScoreService::perPageForScores()` se eliminará o reemplazará por un entero calculado en el controlador. `PaginationService` conservará `Request` porque representa una frontera HTTP reutilizable.

## Servicios

### EvaluationPlanService

- `queryForUser(User $user, array $filters)`.
- `create(array $validated, User $actor)`.
- `update(EvaluationPlan $plan, array $validated, User $actor)` cuando el actor sea necesario.
- Mantener pesos, ítems, estados y transacciones actuales.

### StudentScoreService

- `queryForUser(User $user, array $filters)`.
- `create(array $validated, User $actor)`.
- `bulkCreate(array $validated, User $actor)`.
- `update(StudentScore $score, array $validated, User $actor)`.
- Mantener validación de estudiantes, duplicados, notas finales y relaciones.

### StudentEvaluationScoreService

- `queryForUser(User $user, array $filters)`.
- `store(array $validated, User $actor)`.
- `update(StudentEvaluationScore $score, array $validated, User $actor)`.
- Conservar aprobación del plan, calificación cualitativa/cuantitativa y recálculo automático.

### ManualScoreService

- `query(array $filters)`.
- Los métodos de escritura ya basados en arrays conservarán esa interfaz.
- Reemplazar usos de `Auth::id()` por `User $actor` o su ID explícito.

## Controladores

Cada controlador:

1. Autoriza la operación.
2. Valida el request.
3. Construye filtros permitidos mediante `only()`.
4. Obtiene `$request->user()`.
5. Delega al Service.
6. Conserva exactamente la respuesta API actual.

## Pruebas

- Mantener todas las pruebas Feature actuales.
- Añadir pruebas directas para Services sin construir objetos `Request`.
- Cubrir al menos:
  - Filtros por rol.
  - Creación y actualización con actor explícito.
  - Calificación masiva.
  - Recálculo después de notas por ítem.
  - Ausencia de dependencias `Request` y `Auth` en los cuatro Services.

## Verificación

```bash
docker exec sabere-backend-sabere.test-1 ./vendor/bin/pint
docker exec sabere-backend-sabere.test-1 php artisan test
docker exec sabere-backend-sabere.test-1 npm run build
```

## Criterios de aceptación

- Ninguno de los cuatro Services importa o recibe `Request`.
- Ninguno de los cuatro Services usa el facade `Auth`.
- Los contratos, códigos HTTP y reglas de negocio permanecen sin cambios.
- Las pruebas pueden invocar las reglas centrales con arrays y usuarios explícitos.
