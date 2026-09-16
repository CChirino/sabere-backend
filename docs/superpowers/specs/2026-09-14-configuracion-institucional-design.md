# Diseño — Fase 3.1: Configuración institucional

## Contexto
Sabere se despliega como una instancia independiente por colegio. Cada instancia necesita datos propios del plantel y parámetros académicos sin modificar código. Esta fase crea un módulo de **Settings** tipados que sirva a la vez para:
- Datos del plantel (nombre, RIF, DEA, dirección, teléfonos).
- Parámetros académicos (nota mínima aprobatoria, nº de lapsos, escala por nivel, año escolar activo).
- Branding (logo, colores, firma/sello digital).
- Configuración PWA (nombre, tema, iconos derivados).

## Modelo de datos

### Tabla `settings`
```
id                bigIncrements
group             string       // institution | academic | branding | pwa
key               string
value             json
type              enum/string  // string | integer | boolean | json | file
is_public         boolean      // visible sin autenticación
description       text
unique(group, key)
timestamps
```

- `value` se castea según `type`.
- `type = file` indica que `value` es un path relativo a `storage/app/public`.

## Categorías iniciales

### `institution`
- `name` — string, público
- `rif` — string
- `dea_code` — string
- `address` — string
- `phone` — string
- `email` — string

### `academic`
- `min_passing_score` — integer (por defecto 10)
- `term_count` — integer (por defecto 3)
- `active_academic_period_id` — integer (clave foránea lógica)
- `qualitative_levels` — json (`[{"A":"Sobresaliente"}, ...]`)
- `passing_label` — string ("Aprobado")

### `branding`
- `primary_color` — string (#XXXXXX)
- `secondary_color` — string (#XXXXXX)
- `logo_path` — file
- `seal_path` — file
- `director_signature_path` — file

### `pwa`
- `short_name` — string, público
- `theme_color` — string
- `background_color` — string

## Helper global
```php
function setting(string $key, mixed $default = null): mixed
```
Clave de acceso: `{group}.{key}`.

## Backend
- `App\Models\Setting` con casts y accessor por tipo.
- `App\Http\Controllers\Web\Admin\SettingController`:
  - `index()` → Inertia `Admin/Settings/Index.vue` con settings agrupados.
  - `update(Request $request)` → valida por tipo, guarda value, maneja archivos.
- Ruta pública opcional: `GET /api/v1/settings/public` con `is_public = true`.

## Frontend
- `resources/js/Pages/Admin/Settings/Index.vue`.
- Tabs/secciones por `group`.
- Inputs dinámicos según `type`.
- Upload con preview para logo, sello y firma.

## Integraciones inmediatas
- `AuthenticatedLayout.vue`: leer `institution.name`, `branding.logo_path`, colores.
- `DocumentGenerator`: leer firma, sello y nombre para PDFs.
- PWA manifest: leer `pwa.short_name`, `pwa.theme_color`, etc.

## Tests
- `tests/Feature/Admin/SettingTest.php`:
  - Lectura de settings.
  - Actualización con validación de tipo.
  - Upload de logo.
  - Endpoint público devuelve solo `is_public = true`.

## Verificación
- `./vendor/bin/pint`
- `php artisan test`
- `npx vue-tsc --noEmit`
- `npm run build`
