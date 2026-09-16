# Sabere — Sistema de Gestión Escolar (Venezuela)

Aplicación web para la administración académica de colegios en Venezuela. Desarrollada con **Laravel 12**, **Inertia.js**, **Vue 3**, **TypeScript**, **Vite**, **Tailwind CSS** y **MySQL**. Incluye PWA, notificaciones en tiempo real mediante **Laravel Reverb** y autenticación con roles usando **Spatie Permission**.

## Requisitos

- PHP 8.3+
- Composer
- Node.js 22+
- MySQL 8
- Docker y Docker Compose (opcional, recomendado via Laravel Sail)

## Instalación rápida

```bash
# Clonar el repositorio
git clone <repo>
cd sabere-backend

# Instalar dependencias
composer install
npm install

# Entorno
cp .env.example .env
php artisan key:generate

# Base de datos (con Sail)
docker exec sabere-backend-sabere.test-1 php artisan migrate
docker exec sabere-backend-sabere.test-1 php artisan db:seed
```

## Comandos de desarrollo

```bash
# Iniciar entorno completo (Docker/Sail)
composer dev

# O individualmente
php artisan serve          # http://localhost:5500
npm run dev                # Vite dev server
php artisan queue:listen --tries=1
php artisan reverb:start   # WebSockets

# Build de producción
npm run build

# Tests
composer test              # php artisan test
php artisan test --filter=Authorization

# Lint / formato
./vendor/bin/pint
npx vue-tsc --noEmit

# E2E
npm run test:e2e
```

## Arquitectura

- **Rutas duales**:
  - `routes/web.php` — vistas Inertia (SPA).
  - `routes/api.php` — API JSON bajo `/api/v1/`.
- **Controladores**:
  - Web: `app/Http/Controllers/Web/`
  - API: `app/Http/Controllers/Api/V1/`
- **Roles**: `admin`, `director`, `coordinator`, `teacher`, `student`, `guardian`.
- **Autorización**: Policies de Laravel por recurso + middleware `role:`.

## Seguridad

- Validación mediante `$request->validated()` en controladores API.
- Policies para prevención de IDOR (acceso a recursos ajenos).
- Rate limiting en login, registro y rutas protegidas.
- Registro público controlado por `ALLOW_PUBLIC_REGISTRATION`.
- Tokens Sanctum con expiración y prefijo configurables.

## Convenciones

- Código y UI en español.
- Tests Feature y Unit en `tests/`.
- Migrations con nombre basado en fecha.

## Licencia

MIT
