<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recordatorio de evento</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 30px;
        }
        .event-title {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            margin: 20px 0;
            font-size: 18px;
            font-weight: 600;
        }
        .info-item {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
        .info-label {
            color: #6c757d;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .info-value {
            color: #2d3748;
            font-size: 16px;
            font-weight: 600;
        }
        .date-highlight {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            color: #8b4513;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
            font-weight: 600;
        }
        .type-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            color: white;
        }
        .type-academic { background-color: #3B82F6; }
        .type-sports { background-color: #10B981; }
        .type-cultural { background-color: #8B5CF6; }
        .type-administrative { background-color: #F59E0B; }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 25px;
            margin-top: 20px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📅 Recordatorio de evento</h1>
        </div>
        <div class="content">
            <p>Hola <strong>{{ $user->name }}</strong>,</p>
            <p>Te recordamos que <strong>mañana</strong> se realizará el siguiente evento:</p>

            <div class="event-title">
                {{ $eventTitle }}
            </div>

            <div class="info-item">
                <div class="info-label">Tipo de evento</div>
                <div class="info-value">
                    <span class="type-badge type-{{ $eventType }}">{{ $typeLabel }}</span>
                </div>
            </div>

            <div class="date-highlight">
                📅 {{ $startDate }}
                @if($endDate)
                    — {{ $endDate }}
                @endif
            </div>

            @if($location)
            <div class="info-item">
                <div class="info-label">Ubicación</div>
                <div class="info-value">📍 {{ $location }}</div>
            </div>
            @endif

            @if($description)
            <div class="info-item">
                <div class="info-label">Descripción</div>
                <div class="info-value">{{ $description }}</div>
            </div>
            @endif

            <center>
                <a href="{{ url('/events/calendar') }}" class="btn">Ver calendario</a>
            </center>
        </div>
        <div class="footer">
            <p>© {{ date('Y') }} {{ config('app.name') }} - Sistema de Gestión Académica</p>
            <p>Este es un correo automático, por favor no responder.</p>
        </div>
    </div>
</body>
</html>
