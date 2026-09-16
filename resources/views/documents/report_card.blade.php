@extends('documents._base')

@section('title', 'Boletín de Calificaciones')
@section('document_title', 'Boletín de Calificaciones')

@section('content')
    <h2>Estudiante</h2>
    <p><strong>Nombre:</strong> {{ $student->name }}</p>
    <p><strong>Grado/Sección:</strong> {{ $section ?? 'N/A' }}</p>
    <p><strong>Período:</strong> {{ $periodName }}</p>

    <table>
        <thead>
            <tr>
                <th>Materia</th>
                <th>Nota</th>
                <th>Literal</th>
                <th>Observaciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($scores as $score)
                <tr>
                    <td>{{ $score['subject'] }}</td>
                    <td>{{ $score['score'] }}</td>
                    <td>{{ $score['letter'] }}</td>
                    <td>{{ $score['observations'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No hay calificaciones registradas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if (isset($average))
        <p><strong>Promedio general:</strong> {{ $average }}</p>
    @endif

    <div class="verification">
        Verifique este documento en: {{ $verificationUrl ?? '#' }}
    </div>
@endsection
