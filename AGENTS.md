# AGENTS.md

## Project

Sabere — school management system (Venezuela). Laravel 12 + Inertia.js + Vue 3 + TypeScript + Vite + Tailwind CSS + MySQL. PWA-enabled.

## Commands

```bash
# Dev — the app runs in Docker/Sail: container sabere-backend-sabere.test-1 (http://localhost:5500, maps 5500->80)
# Host-side WSL php artisan works only for non-DB commands (DB_HOST=mysql is Docker-internal)
composer dev
# or individually:
php artisan serve          # port 5500
npm run dev                # vite on 5173
php artisan queue:listen --tries=1
php artisan reverb:start   # websockets on 8080

# Build (typecheck is part of build — vue-tsc runs before vite)
npm run build              # runs: vue-tsc && vite build

# Typecheck only
npx vue-tsc --noEmit

# Test (clears config cache first)
composer test              # or: php artisan config:clear && php artisan test
php artisan test --filter=Auth   # single suite/file

# Lint / format
./vendor/bin/pint          # Laravel Pint (PHP CS Fixer)

# DB (runs inside Sail container — .env points DB_HOST=mysql, only resolvable in Docker)
docker exec sabere-backend-sabere.test-1 php artisan migrate
docker exec sabere-backend-sabere.test-1 php artisan db:seed   # PermissionSeeder -> RoleSeeder -> UserSeeder -> ... -> DemoSeeder
# Demo users: admin@sabere.com / director@ / coordinador@ / profesor@ / estudiante@ / representante@sabere.com — all password: "password"

# E2E (Playwright; requires server running + seeded DB)
npm run test:e2e

# One-off
php artisan events:send-reminders   # scheduled daily at 08:00
node scripts/generate-icons.mjs     # regenerate PWA icons + favicon from public/icons/icon.svg (uses Playwright chromium)
```

## Architecture

- **Dual routing**: `routes/web.php` serves Inertia pages (Vue SPA); `routes/api.php` serves JSON API under `/api/v1/`.
- **Controllers split**: `app/Http/Controllers/Web/` (Inertia) vs `app/Http/Controllers/Api/V1/` (JSON). Auth controllers are Breeze-generated under `app/Http/Controllers/Auth/`.
- **Academic API routes split by method**: `routes/academic_read.php` (GET, all roles incl. students) vs `routes/academic.php` (POST/PUT/DELETE, staff only). Both `require`d from `api.php`.
- **Vue pages mirror roles**: `resources/js/Pages/{Admin,Academic,Teacher,Student,Guardian,Coordinator,Events,Circulars,Help}/`.
- **Layouts**: `AuthenticatedLayout.vue` (logged-in), `GuestLayout.vue` (auth pages), `AppLayout.vue` (base).

## Frontend Conventions

- **TS path alias**: `@/*` → `resources/js/*` (see `tsconfig.json`).
- **Ziggy**: `ziggy-js` aliased to `vendor/tightenco/ziggy`; use `route('name', params)` globally in Vue.
- **Types**: shared in `resources/js/types/index.d.ts`. All entities (`User`, `Task`, `Enrollment`, etc.) defined there. `PageProps<T>` wraps Inertia props with `auth.user` + `flash`.
- **Composables**: `useApi()` (fetch-based, CSRF-aware, in `composables/useApi.ts`) and `useAuth()` (role helpers from Inertia page props, in `composables/useAuth.ts`).
- **Inertia shared props** (from `HandleInertiaRequests`): `auth.user` (sanitized user data + roles), `flash.{success,error,warning}`.
- **WebSockets**: Echo + Pusher configured globally in `bootstrap.ts` with Reverb broadcaster. Channel auth in `routes/channels.php`.
- **CSP**: `SecurityHeaders.php` agrega `Content-Security-Policy`. En producción, configurar `REVERB_HOST`/`VITE_REVERB_HOST` con el dominio real; si se deja `localhost`, Echo se omite en el cliente para evitar errores de conexión.
- **No frontend tests** — only PHP tests (`tests/Feature`, `tests/Unit`).

## Roles & Authorization

Six roles via Spatie Permission: `admin`, `director`, `coordinator`, `teacher`, `student`, `guardian`. Middleware alias: `role:admin|director`. Permission middleware also registered. Role-permission matrix in `database/seeders/RoleSeeder.php`.

Frontend role helpers via `useAuth()`: `hasRole()`, `isAdmin`, `isStaff` (admin|director|coordinator), `canManageAcademic` (staff+teacher), `primaryRole`, `roleLabel`.

## Key Libraries

| Package | Purpose |
|---|---|
| `inertiajs/inertia-laravel` + `@inertiajs/vue3` | SPA bridge |
| `laravel/sanctum` | API auth (stateful SPA + token) |
| `laravel/reverb` + `laravel-echo` + `pusher-js` | WebSockets (section chat) |
| `spatie/laravel-permission` | RBAC |
| `spatie/laravel-activitylog` | Audit log on User model |
| `maatwebsite/excel` | User bulk import (XLSX) |
| `laravel/socialite` + `google/apiclient` | Google OAuth |
| `tightenco/ziggy` | Laravel routes in JS |
| `jspdf` + `html2canvas` | Client-side PDF export |

## Environment

- Locale: `es`, Timezone: `America/Caracas`, Faker: `es_VE`
- DB: MySQL 8 (Sail). Tests override to SQLite `:memory:` (see `phpunit.xml`).
- Queue/cache/session: `database` in production, overridden to `array`/`sync` in tests.
- CORS: configured via `CORS_ALLOWED_ORIGINS` env var (see `config/cors.php`).
- Public registration gated by `ALLOW_PUBLIC_REGISTRATION` env var.

## Conventions

- Spanish comments and UI strings throughout codebase.
- API routes versioned (`v1` prefix). Web routes use named routes (`admin.users.index`, `teacher.tasks.store`, etc.).
- Migrations use date-based naming: `YYYY_MM_DD_NNNNNN_description.php`.
- Inertia pages receive data via `Inertia::render('PageName', ['propName' => $data])` — always check the controller for prop names.
