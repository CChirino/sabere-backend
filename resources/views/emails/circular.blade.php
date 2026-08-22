<x-mail::message>
# {{ $circular->title }}

{{ $circular->content }}

<x-mail::button :url="route('circulars.show', $circular->id)">
Ver Circular
</x-mail::button>

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
