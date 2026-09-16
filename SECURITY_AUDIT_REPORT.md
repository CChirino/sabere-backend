# 🔒 Reporte de Auditoría de Seguridad (Pentesting)
## Proyecto: Sabere Backend
## Fecha: 5 de Marzo de 2026

---

## Resumen Ejecutivo

Se realizó una auditoría de seguridad estática (code review / white-box pentesting) sobre el código fuente de la aplicación **Sabere Backend**, un sistema de gestión escolar construido con Laravel + Inertia.js + Vue.js.

| Severidad | Cantidad |
|-----------|----------|
| **CRÍTICA** | 3 |
| **ALTA** | 5 |
| **MEDIA** | 6 |
| **BAJA** | 4 |
| **INFORMATIVA** | 3 |

---

## VULNERABILIDADES CRÍTICAS 🔴

### CRIT-01: Mass Assignment en múltiples controladores API
**Severidad:** CRÍTICA  
**CVSS:** 8.6  
**Tipo:** CWE-915 (Improperly Controlled Modification of Dynamically-Determined Object Attributes)

**Descripción:**  
Múltiples controladores utilizan `$request->all()` directamente en `create()` y `update()` de Eloquent. Aunque los modelos tienen `$fillable` definido, el patrón `$request->all()` pasa TODOS los campos del request (incluidos los validados y los no validados) al modelo. Si un campo está en `$fillable` pero no se valida (ej: `student_id`, `status`), un atacante puede inyectar valores arbitrarios.

**Archivos afectados:**
- `app/Http/Controllers/Api/V1/Academic/EnrollmentController.php` → líneas 104, 164
- `app/Http/Controllers/Api/V1/Academic/TaskController.php` → líneas 103, 167
- `app/Http/Controllers/Api/V1/Academic/StudentScoreController.php` → línea 170
- `app/Http/Controllers/Api/V1/Academic/StudentGuardianController.php` → líneas 88, 139
- Todos los controladores API Academic que usan `$request->all()` en store/update

**Ejemplo de ataque:**
```
PUT /api/v1/student-scores/{id}
Content-Type: application/json
Authorization: Bearer <token_profesor>

{
    "score": 18,
    "student_id": 999,        // INYECCIÓN: cambiar a otro estudiante
    "graded_by": 1,            // INYECCIÓN: suplantar calificador
    "subject_assignment_id": 5 // INYECCIÓN: cambiar materia
}
```

En `StudentScoreController::update()` (línea 170): `$score->update($request->all())` actualizará TODOS los campos que estén en `$fillable`, no solo los validados.

**Remediación:**
```php
// ❌ VULNERABLE
$score->update($request->all());

// ✅ SEGURO - Solo pasar campos validados
$score->update($request->only(['score', 'observations', 'is_final']));
// O usar $request->validated() si se usa Form Request
```

---

### CRIT-02: IDOR (Insecure Direct Object Reference) en rutas API académicas
**Severidad:** CRÍTICA  
**CVSS:** 8.1  
**Tipo:** CWE-639 (Authorization Bypass Through User-Controlled Key)

**Descripción:**  
Las rutas académicas en `routes/academic.php` están protegidas solo por el middleware `role:admin|director|coordinator|teacher|student`. Esto significa que **cualquier estudiante autenticado** puede acceder a operaciones CRUD completas sobre recursos que no le pertenecen, incluyendo:

- **Crear/editar/eliminar inscripciones** de otros estudiantes
- **Ver calificaciones** de cualquier estudiante
- **Ver/modificar horarios** de cualquier sección
- **Acceder a datos de representantes** de cualquier estudiante

**Archivos afectados:**
- `routes/api.php` línea 66: `Route::middleware(['role:admin|director|coordinator|teacher|student'])`
- `routes/academic.php` → Todas las rutas `apiResource` no tienen verificación de propiedad

**Ejemplo de ataque:**
```
# Un estudiante puede ver las calificaciones de CUALQUIER otro estudiante:
GET /api/v1/student-scores/by-student/42
Authorization: Bearer <token_estudiante>

# Un estudiante puede ver las inscripciones de cualquiera:
GET /api/v1/enrollments/by-student/42

# Un estudiante podría CREAR inscripciones:
POST /api/v1/enrollments
Authorization: Bearer <token_estudiante>
{"student_id": 42, "section_id": 1, "academic_period_id": 1, "enrollment_date": "2026-03-01"}

# Un estudiante puede ELIMINAR cualquier recurso:
DELETE /api/v1/enrollments/15
DELETE /api/v1/schedules/10
DELETE /api/v1/student-guardians/5
```

**Remediación:**
1. Separar las rutas de solo lectura (estudiante) de las de escritura (admin/teacher)
2. Implementar Policies de Laravel para cada modelo
3. Agregar verificación de propiedad en cada endpoint

```php
// En routes/academic.php - Separar permisos:
Route::middleware(['role:admin|director|coordinator|teacher'])->group(function () {
    Route::apiResource('enrollments', EnrollmentController::class)->except(['index', 'show']);
});

Route::middleware(['role:admin|director|coordinator|teacher|student'])->group(function () {
    Route::apiResource('enrollments', EnrollmentController::class)->only(['index', 'show']);
});

// En controladores - Agregar verificación de propiedad:
public function show(int $id): JsonResponse
{
    $enrollment = Enrollment::find($id);
    $this->authorize('view', $enrollment); // Policy
    // ...
}
```

---

### CRIT-03: Registro de usuarios sin asignación de rol y sin verificación de email
**Severidad:** CRÍTICA  
**CVSS:** 7.5  
**Tipo:** CWE-269 (Improper Privilege Management)

**Descripción:**  
El `RegisterController` de la API (`app/Http/Controllers/Api/V1/Auth/RegisterController.php`) crea usuarios **sin asignarles ningún rol**. Además, el `RegisteredUserController` web tampoco asigna rol. Combinado con que `MustVerifyEmail` está comentado en `User.php` (línea 5), cualquier persona puede:

1. Registrarse libremente via API o web
2. Obtener un token Sanctum inmediatamente
3. Acceder al dashboard sin verificación de email

**Archivos afectados:**
- `app/Http/Controllers/Api/V1/Auth/RegisterController.php` → No asigna rol
- `app/Http/Controllers/Auth/RegisteredUserController.php` → No asigna rol
- `app/Models/User.php` línea 5 → `MustVerifyEmail` comentado

**Remediación:**
```php
// En RegisterController.php
public function register(RegisterRequest $request)
{
    $user = $this->create($request->validated());
    $user->assignRole('student'); // Asignar rol por defecto
    // ...
}

// En User.php - Descomentar:
use Illuminate\Contracts\Auth\MustVerifyEmail;
class User extends Authenticatable implements MustVerifyEmail
```

---

## VULNERABILIDADES ALTAS 🟠

### HIGH-01: Tokens Sanctum sin expiración
**Severidad:** ALTA  
**CVSS:** 7.0  
**Tipo:** CWE-613 (Insufficient Session Expiration)

**Descripción:**  
En `config/sanctum.php` línea 50: `'expiration' => null`. Los tokens API nunca expiran. Si un token es comprometido, el atacante tiene acceso indefinido.

**Remediación:**
```php
// config/sanctum.php
'expiration' => 1440, // 24 horas en minutos (ajustar según necesidad)
```

---

### HIGH-02: Sin Rate Limiting en API de login
**Severidad:** ALTA  
**CVSS:** 7.0  
**Tipo:** CWE-307 (Improper Restriction of Excessive Authentication Attempts)

**Descripción:**  
El endpoint `POST /api/v1/login` en `LoginController` no implementa rate limiting. Aunque el `LoginRequest` del login web sí tiene rate limiting (5 intentos), el controlador API usa `LoginRequest` solo para validación, **no llama a `ensureIsNotRateLimited()`** ni usa `RateLimiter`.

**Archivo afectado:** `app/Http/Controllers/Api/V1/Auth/LoginController.php`

El método `login()` llama a `$request->validated()` y luego `Auth::attempt()`, pero nunca incrementa ni verifica el rate limiter. Un atacante puede hacer fuerza bruta ilimitada al endpoint API.

**Remediación:**
```php
// En api.php - Agregar throttle al login
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');
Route::post('/register', [RegisterController::class, 'register'])->middleware('throttle:3,1');
```

---

### HIGH-03: Sin Rate Limiting en rutas API protegidas
**Severidad:** ALTA  
**CVSS:** 6.5  
**Tipo:** CWE-770 (Allocation of Resources Without Limits or Throttling)

**Descripción:**  
Ninguna ruta de la API (`routes/api.php`, `routes/academic.php`) tiene middleware `throttle`. Un usuario autenticado puede hacer un número ilimitado de requests, causando DoS o extracción masiva de datos.

**Remediación:**
```php
// En api.php - Agregar throttle global
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // ... rutas protegidas
});
```

---

### HIGH-04: Guardian puede acceder a datos de estudiantes ajenos via API
**Severidad:** ALTA  
**CVSS:** 7.2  
**Tipo:** CWE-639 (IDOR)

**Descripción:**  
Las rutas de guardian en `routes/api.php` (líneas 93-98) reciben `{studentId}` como parámetro URL pero **no verifican que el guardian tenga relación con ese estudiante** (excepto en `studentInfo`).

```php
// Líneas 94-98 - Sin verificación de parentesco:
Route::get('student/{studentId}/scores', function ($studentId) { ... });
Route::get('student/{studentId}/tasks', function ($studentId) { ... });
```

Un guardian puede ver las calificaciones y tareas de CUALQUIER estudiante del sistema.

**Remediación:**
```php
Route::get('student/{studentId}/scores', function ($studentId) {
    // Verificar relación guardian-estudiante
    $hasAccess = StudentGuardian::where('guardian_id', auth()->id())
        ->where('student_id', $studentId)
        ->where('status', true)
        ->exists();
    if (!$hasAccess) abort(403);
    // ...
});
```

---

### HIGH-05: Exposición de excepciones en Google OAuth
**Severidad:** ALTA  
**CVSS:** 5.3  
**Tipo:** CWE-209 (Information Exposure Through an Error Message)

**Descripción:**  
En `SocialAuthController.php` línea 58: `'error' => $e->getMessage()`. Los mensajes de excepción internos se exponen directamente al usuario, pudiendo revelar detalles de configuración, rutas de archivos o stack traces.

**Remediación:**
```php
} catch (\Exception $e) {
    \Log::error('Google OAuth Error: ' . $e->getMessage());
    return $this->sendError('Error al autenticar con Google', [], 401);
}
```

---

## VULNERABILIDADES MEDIAS 🟡

### MED-01: Session Encrypt deshabilitado
**Severidad:** MEDIA  
**CVSS:** 4.3  
**Tipo:** CWE-311 (Missing Encryption of Sensitive Data)

**Descripción:**  
`config/session.php` línea 50: `'encrypt' => env('SESSION_ENCRYPT', false)` y `.env.example` línea 33: `SESSION_ENCRYPT=false`. Los datos de sesión se almacenan sin encriptar.

**Remediación:** Cambiar `SESSION_ENCRYPT=true` en `.env`

---

### MED-02: Secure Cookie no forzado
**Severidad:** MEDIA  
**CVSS:** 4.3  
**Tipo:** CWE-614 (Sensitive Cookie in HTTPS Session Without 'Secure' Attribute)

**Descripción:**  
`config/session.php` línea 172: `'secure' => env('SESSION_SECURE_COOKIE')` sin valor por defecto. En producción, si no se configura `SESSION_SECURE_COOKIE=true`, las cookies de sesión se enviarán por HTTP sin cifrar.

**Remediación:**
```
# .env (producción)
SESSION_SECURE_COOKIE=true
```

---

### MED-03: Falta validación de tipo de archivo en task submissions
**Severidad:** MEDIA  
**CVSS:** 5.3  
**Tipo:** CWE-434 (Unrestricted Upload of File with Dangerous Type)

**Descripción:**  
`TaskSubmissionController::store()` (líneas 51-53) solo valida tamaño (`max:10240`) pero **no restringe tipos de archivo**. Un atacante podría subir archivos `.php`, `.phtml`, `.svg` (con XSS), `.html`, etc.

```php
'file' => 'nullable|file|max:10240', // Sin restricción de tipo
'files.*' => 'file|max:10240',       // Sin restricción de tipo
```

**Remediación:**
```php
'file' => 'nullable|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,zip,rar,txt',
'files.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,zip,rar,txt',
```

---

### MED-04: Falta CORS configuration file
**Severidad:** MEDIA  
**CVSS:** 4.3  
**Tipo:** CWE-942 (Permissive Cross-domain Policy with Untrusted Domains)

**Descripción:**  
No existe archivo `config/cors.php`. En Laravel 11+, CORS se configura en el middleware stack. Si no está configurado explícitamente, podría permitir requests cross-origin no deseados a la API.

**Remediación:** Verificar la configuración CORS en `bootstrap/app.php` o crear `config/cors.php` con dominios permitidos explícitos.

---

### MED-05: Admin puede eliminarse a sí mismo via API
**Severidad:** MEDIA  
**CVSS:** 4.3  
**Tipo:** CWE-269

**Descripción:**  
En `Api\V1\Admin\UserController::destroy()` (líneas 87-98), se previene eliminar usuarios con rol `super_admin` o `admin`, pero un admin con rol `director` sí puede eliminar otros admins. Además, el Web `UserController::destroy()` previene auto-eliminación pero **no previene eliminar admins**.

**Remediación:**
```php
// Web UserController::destroy()
if ($user->hasAnyRole(['super_admin', 'admin'])) {
    return back()->with('error', 'No se puede eliminar un administrador.');
}
```

---

### MED-06: Spatie Permission Service Provider no registrado
**Severidad:** MEDIA  
**CVSS:** 5.0  

**Descripción:**  
`bootstrap/providers.php` solo registra `AppServiceProvider`. El `PermissionServiceProvider` de Spatie no está explícitamente registrado. Si el auto-discovery falla (ej: en cache), el middleware `role:` dejará de funcionar, exponiendo rutas protegidas.

**Remediación:**
```php
// bootstrap/providers.php
return [
    App\Providers\AppServiceProvider::class,
    Spatie\Permission\PermissionServiceProvider::class,
];
```

---

## VULNERABILIDADES BAJAS 🟢

### LOW-01: Tokens Sanctum sin prefijo
**Severidad:** BAJA  
**CVSS:** 2.0  

`config/sanctum.php` línea 65: `'token_prefix' => env('SANCTUM_TOKEN_PREFIX', '')`. Sin prefijo, los tokens no son detectados por GitHub Secret Scanning u otros servicios de detección de leaks.

**Remediación:** `SANCTUM_TOKEN_PREFIX=sabere_`

---

### LOW-02: APP_DEBUG probablemente habilitado
**Severidad:** BAJA  
**CVSS:** 3.7  

`.env.example` tiene `APP_DEBUG=true`. Si esto se replica en producción, se expondrán stack traces, variables de entorno y detalles de la BD.

**Remediación:** Asegurar `APP_DEBUG=false` en producción.

---

### LOW-03: Password timeout muy largo
**Severidad:** BAJA  
**CVSS:** 2.0  

`config/auth.php` línea 113: `'password_timeout' => 10800` (3 horas). El timeout para confirmar contraseña es excesivo.

**Remediación:** Reducir a 900 (15 minutos).

---

### LOW-04: Falta logout en web - no invalida tokens API
**Severidad:** BAJA  
**CVSS:** 3.0  

El logout web invalida la sesión pero no revoca los tokens Sanctum del usuario. Si un usuario tenía tokens API activos, estos seguirán siendo válidos.

**Remediación:**
```php
// En AuthenticatedSessionController::destroy()
$request->user()->tokens()->delete(); // Revocar todos los tokens API
```

---

## INFORMATIVAS ℹ️

### INFO-01: ContactController referenciado pero no importado
`routes/web.php` línea 33 usa `ContactController::class` pero no tiene `use` statement al inicio. Esto generará un error 500 si se accede a `/contact`.

### INFO-02: Ruta `/api/user` duplicada
`routes/api.php` define `GET /api/user` dos veces (líneas 31-35 y 43-45), una dentro de `v1` prefix y otra fuera.

### INFO-03: Falta de paginación en múltiples endpoints
Los controladores `index()` de la mayoría de recursos API usan `->get()` sin paginación. Con una base de datos grande, un atacante autenticado podría extraer todos los registros y causar carga excesiva en el servidor.

---

## Resumen de Remediaciones Prioritarias

| # | Acción | Esfuerzo | Impacto |
|---|--------|----------|---------|
| 1 | Reemplazar `$request->all()` por `$request->only()` o `$request->validated()` | Bajo | CRÍTICO |
| 2 | Separar rutas API académicas por rol (leer vs escribir) | Medio | CRÍTICO |
| 3 | Implementar Laravel Policies para verificar propiedad de recursos | Medio | CRÍTICO |
| 4 | Agregar rate limiting a todos los endpoints API | Bajo | ALTO |
| 5 | Configurar expiración de tokens Sanctum | Bajo | ALTO |
| 6 | Asignar rol por defecto en registro y habilitar MustVerifyEmail | Bajo | CRÍTICO |
| 7 | Validar tipos de archivo en uploads | Bajo | MEDIO |
| 8 | Verificar parentesco guardian-estudiante en rutas API | Bajo | ALTO |
| 9 | Configurar SESSION_ENCRYPT y SESSION_SECURE_COOKIE para producción | Bajo | MEDIO |
| 10 | Registrar Spatie PermissionServiceProvider explícitamente | Bajo | MEDIO |

---

*Reporte generado mediante auditoría estática de código (white-box). Se recomienda complementar con pruebas dinámicas (DAST) y pruebas de penetración de infraestructura.*
