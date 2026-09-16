# Diseño — Boletines y documentos oficiales

## Contexto

Sabere ya puede almacenar notas por lapso y calcular notas anuales y promociones. Faltan los documentos oficiales que los colegios venezolanos necesitan: boletines, constancias y reportes.

## Objetivo

Generar documentos PDF desde el servidor con formato venezolano, accesibles por roles apropiados, con verificación de autenticidad.

## Alcance

- Generación de boletín de calificaciones (por lapso y anual).
- Constancia de estudio.
- Constancia de inscripción.
- Certificación de calificaciones (resumen por período).
- Reporte de matrícula por sección/grado (formato ME/GES).
- Código/Hash de verificación en constancias oficiales.

## Fuera de alcance

- Firma digital avanzada.
- QR con imagen; será un hash consultable por endpoint.

## Dependencia

- `barryvdh/laravel-dompdf` para renderizar PDFs desde vistas Blade.

## Arquitectura

- `app/Services/Pdf/DocumentGenerator.php`: servicio central que genera PDF a partir de Blade + datos.
- `app/Http/Controllers/Api/V1/Academic/DocumentController.php`: endpoints para generar y descargar documentos.
- `resources/views/documents/`: plantillas Blade para cada tipo de documento.
- `app/Models/DocumentVerification.php`: registro de documentos generados con `hash` y `expires_at`.

## Modelo de datos

### `DocumentVerification`

| Campo | Descripción |
|---|---|
| `document_type` | `report_card`, `study_certificate`, `enrollment_certificate`, `score_certification`, `enrollment_report` |
| `document_id` | Identificador del recurso (estudiante, período, etc.) |
| `hash` | Hash único para verificación pública |
| `generated_by` | Usuario que generó el documento |
| `expires_at` | Vencimiento opcional |
| `metadata` | JSON con datos del documento |

## Endpoints API

- `GET /api/v1/documents/report-card/{studentId}/{termId}`
- `GET /api/v1/documents/report-card-annual/{studentId}/{academicPeriodId}`
- `GET /api/v1/documents/study-certificate/{studentId}`
- `GET /api/v1/documents/enrollment-certificate/{studentId}`
- `GET /api/v1/documents/score-certification/{studentId}/{academicPeriodId}`
- `GET /api/v1/documents/enrollment-report/{sectionId}`
- `GET /api/v1/documents/verify/{hash}`

## Reglas

1. Boletines y certificaciones: estudiante ve lo propio; representantes ven a sus estudiantes; staff y profesores ven según sección/asignación.
2. Documentos oficiales generan un `DocumentVerification` con hash.
3. `verify/{hash}` devuelve metadatos si el documento existe y no ha vencido.

## Tests

- Descarga de boletín en PDF.
- Acceso denegado a boletín de otro estudiante.
- Verificación de hash.
