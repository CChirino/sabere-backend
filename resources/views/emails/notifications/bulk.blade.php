<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
            margin-bottom: 20px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #2563eb;
        }
        .priority-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 15px;
        }
        .priority-low { background-color: #10b981; color: white; }
        .priority-normal { background-color: #3b82f6; color: white; }
        .priority-high { background-color: #f59e0b; color: white; }
        .priority-urgent { background-color: #ef4444; color: white; }
        .subject {
            font-size: 22px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 20px;
        }
        .message {
            font-size: 16px;
            color: #4b5563;
            line-height: 1.8;
            white-space: pre-wrap;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            text-align: center;
            color: #9ca3af;
            font-size: 14px;
        }
        .sender {
            margin-top: 20px;
            padding: 15px;
            background-color: #f9fafb;
            border-radius: 6px;
            font-size: 14px;
            color: #6b7280;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            margin-top: 20px;
        }
        .btn:hover {
            background-color: #1d4ed8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Saberé</div>
            <p style="color: #6b7280; margin-top: 5px;">Sistema de Gestión Escolar</p>
        </div>

        @php
            $priorityClass = match($priority) {
                'low' => 'priority-low',
                'normal' => 'priority-normal',
                'high' => 'priority-high',
                'urgent' => 'priority-urgent',
                default => 'priority-normal',
            };
            $priorityLabel = match($priority) {
                'low' => 'Baja',
                'normal' => 'Normal',
                'high' => 'Alta',
                'urgent' => 'Urgente',
                default => 'Normal',
            };
        @endphp

        <span class="priority-badge {{ $priorityClass }}">{{ $priorityLabel }} Prioridad</span>

        <div class="subject">{{ $subject }}</div>

        <div class="message">
            {{ $message }}
        </div>

        <div class="sender">
            <strong>Enviado por:</strong> {{ $senderName }}
            @if($senderEmail)
                <br><strong>Contacto:</strong> {{ $senderEmail }}
            @endif
            <br><strong>Fecha:</strong> {{ now()->format('d/m/Y H:i') }}
        </div>

        <div class="footer">
            <p>Este es un mensaje automático del sistema de notificaciones de {{ config('app.name') }}.</p>
            <p>Si tienes alguna pregunta, por favor contacta a la administración.</p>
            <p style="margin-top: 15px; font-size: 12px;">
                © {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.
            </p>
        </div>
    </div>
</body>
</html>
