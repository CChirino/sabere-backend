<x-mail::message>
# Nuevo Mensaje de Contacto

Se ha recibido un nuevo mensaje desde el formulario de contacto de **{{ config('app.name') }}**.

---

**Nombre:** {{ $data['name'] }}

**Institución:** {{ $data['institution'] ?? 'No especificada' }}

**Correo:** {{ $data['email'] }}

**Teléfono:** {{ $data['phone'] ?? 'No proporcionado' }}

---

## Mensaje

{{ $data['message'] }}

---

<x-mail::button :url="'mailto:' . $data['email']">
Responder a {{ $data['name'] }}
</x-mail::button>

<small>Este correo fue enviado automáticamente desde el formulario de contacto de {{ config('app.name') }}.</small>
</x-mail::message>
