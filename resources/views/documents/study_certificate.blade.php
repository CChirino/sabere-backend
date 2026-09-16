@extends('documents._base')

@section('title', 'Constancia de Estudio')
@section('document_title', 'Constancia de Estudio')

@section('content')
    <p style="text-align: justify; line-height: 1.6;">
        El suscrito Director/a del <strong>{{ config('app.name', 'Sabere') }}</strong>,
        hace constar que el(la) ciudadano(a) <strong>{{ $student->name }}</strong>,
        cédula escolar/C.I. <strong>{{ $student->identity_number ?? 'N/A' }}</strong>,
        es estudiante regular de este plantel, cursando el
        <strong>{{ $grade ?? 'N/A' }}</strong> grado/año, sección <strong>{{ $section ?? 'N/A' }}</strong>
        durante el período académico <strong>{{ $periodName }}</strong>.
    </p>

    <p style="margin-top: 40px;">Constancia que se expide a solicitud del interesado en la ciudad de ____________ a los {{ now()->day }} días del mes de {{ now()->format('F') }} del {{ now()->year }}.</p>

    <div style="margin-top: 60px; text-align: center;">
        <p>_____________________________</p>
        <p>Director(a)</p>
    </div>

    <div class="verification">
        Código de autenticidad: {{ $hash ?? 'N/A' }} — {{ $verificationUrl ?? '#' }}
    </div>
@endsection
