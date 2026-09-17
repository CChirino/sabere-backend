# Fase 6.5 — Eliminación de N+1 restantes

## Objetivo

Eliminar consultas repetitivas dentro de ciclos en los servicios de coordinación y dashboard, manteniendo contratos, métricas y reglas actuales.

## Alcance

Este lote incluye:

- `CoordinatorDataService::scoresOverview()`
- `CoordinatorDataService::teacherShow()`
- Ciclos de alto costo en `DashboardDataService`
- Pruebas funcionales y de cantidad de consultas para las rutas afectadas

No incluye cache adicional, Redis, cambios de contratos API, DTOs ni conversión de notificaciones a Jobs.

## Estrategia

Se usarán consultas agregadas y cargas por lote:

- Obtener identificadores de la página o conjunto actual.
- Ejecutar una consulta por métrica mediante `whereIn`, `groupBy` y agregados SQL.
- Indexar los resultados en colecciones por identificador.
- Transformar los recursos usando los mapas precargados, sin consultas dentro de cada iteración.

No se aplicará cache para ocultar consultas ineficientes ni se cargarán colecciones completas cuando SQL pueda calcular la métrica.

## CoordinatorDataService

### scoresOverview

Actualmente consulta `StudentScore` por cada asignación paginada. Se reemplazará por una consulta agregada para todas las asignaciones visibles en la página.

La consulta deberá obtener por `subject_assignment_id`:

- Cantidad de notas registradas.
- Promedio de notas.
- Cantidad de estudiantes por debajo de 10 puntos.

Los datos se combinarán con las asignaciones ya cargadas. Se conservarán `items`, `stats` y `pagination`.

### teacherShow

Actualmente calcula entregas pendientes consultando las tareas de cada asignación. Se reemplazará por conteos agrupados por asignación.

Se conservarán:

- Total de asignaciones.
- Total de tareas.
- Total de estudiantes.
- Entregas pendientes por asignación y total.

## DashboardDataService

Se auditarán los métodos por rol y se modificarán únicamente los ciclos que ejecuten consultas por estudiante o asignación.

Prioridades:

- Promedios de estudiantes calculados en lote.
- Conteos de calificaciones por asignación agrupados.
- Conteos de matrículas o entregas reutilizados en vez de recalculados.

Las métricas visibles y sus valores semánticos no cambiarán.

## Pruebas

Se añadirán o ampliarán pruebas para:

- Confirmar resultados de `scoresOverview` con varias asignaciones y notas.
- Confirmar estadísticas del detalle de profesor con varias asignaciones.
- Confirmar métricas de dashboard afectadas.
- Medir que la cantidad de consultas no crezca linealmente al aumentar asignaciones o estudiantes.

Las pruebas de consultas usarán conjuntos equivalentes de distinto tamaño y compararán el incremento, evitando depender de un número absoluto frágil cuando sea posible.

## Verificación

```bash
docker exec sabere-backend-sabere.test-1 ./vendor/bin/pint
docker exec sabere-backend-sabere.test-1 php artisan test
docker exec sabere-backend-sabere.test-1 npm run build
```

## Criterios de aceptación

- No quedan consultas a `StudentScore`, tareas, entregas o matrículas dentro de los ciclos auditados.
- Los contratos y métricas existentes permanecen sin cambios.
- Las pruebas de resultados y regresión de consultas pasan.
- Pint, suite completa y build pasan.
